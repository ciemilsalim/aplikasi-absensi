<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\StudentPermit;
use App\Models\Setting;
use Carbon\Carbon;

class PermitController extends Controller
{
    /**
     * Menampilkan halaman pemindai izin keluar/kembali.
     */
    public function showScanner()
    {
        $students = Student::select('id', 'unique_id', 'name', 'photo')
            ->whereNotNull('photo')
            ->get()
            ->map(function ($student) {
            return [
            'unique_id' => $student->unique_id,
            'name' => $student->name,
            'photo_url' => asset('storage/' . $student->photo),
            ];
        });

        return view('permit-scanner', compact('students'));
    }

    /**
     * Memproses scan untuk izin keluar atau kembali.
     */
    public function storePermit(Request $request)
    {
        // Normalisasi format input dari scanner (student_unique_id / qr_data / pass_code)
        if ($request->has('qr_data') && !$request->has('student_unique_id')) {
            $request->merge(['student_unique_id' => $request->input('qr_data')]);
        }
        if ($request->has('pass_code') && !$request->has('student_unique_id')) {
            $request->merge(['student_unique_id' => $request->input('pass_code')]);
        }

        $request->validate([
            'student_unique_id' => 'required|string',
            'reason'            => 'nullable|string|max:255',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
        ]);

        $qrData = $request->student_unique_id;

        // Validasi Jarak GPS (Jika sekolah mengonfigurasi koordinat & pemindai mengirim koordinat)
        $settings = Setting::pluck('value', 'key');
        $schoolLat = $settings->get('school_latitude');
        $schoolLng = $settings->get('school_longitude');
        $radius = (float) $settings->get('attendance_radius', 100);

        if ($request->filled('latitude') && $request->filled('longitude') && !empty($schoolLat) && !empty($schoolLng)) {
            $distance = $this->haversineDistance($request->latitude, $request->longitude, $schoolLat, $schoolLng);
            if ($distance > $radius) {
                return response()->json([
                    'status' => 'location_error',
                    'message' => "Anda berada di luar radius yang diizinkan (" . round($distance) . " meter dari sekolah).",
                ], 403);
            }
        }

        // 1. Integrasi Utama: Layanan E-Dispensasi Terverifikasi Satpam
        $dispensationsService = app(\App\Services\Permit\StudentDispensationsService::class);
        $scanResult = $dispensationsService->verifyAndProcessScan(
            identifier: $qrData,
            securityActor: $request->user(),
            fallbackReason: $request->reason
        );

        if (isset($scanResult['code']) && $scanResult['code'] === 200) {
            return response()->json($scanResult, 200);
        }

        // 2. Fallback: Parse siswa jika bukan pass code e-dispensasi
        if (strlen($qrData) > 37 && substr($qrData, -37, 1) === '-') {
            $nis = substr($qrData, 0, -37);
            $uniqueId = substr($qrData, -36);
            $student = Student::where('nis', $nis)->where('unique_id', $uniqueId)->firstOrFail();
        } else {
            $student = Student::where('unique_id', $qrData)->orWhere('nis', $qrData)->firstOrFail();
        }
        $today = now()->startOfDay();

        // Cari data absensi masuk hari ini
        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('attendance_time', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'not_clocked_in',
                'message' => 'Siswa harus absen masuk terlebih dahulu.',
                'student_name' => $student->name,
            ], 403);
        }

        $activePermit = StudentPermit::where('attendance_id', $attendance->id)
            ->whereNull('time_in')
            ->first();

        // KASUS 1: Siswa kembali dari izin
        if ($activePermit) {
            $activePermit->update([
                'time_in' => now(),
                'status'  => 'completed',
            ]);

            // Pulihkan status kehadiran sesuai kedatangan pagi hari agar status 'terlambat' tidak terhapus
            $startTimeSetting = $settings->get('start_time', '07:15');
            $clockInDate = Carbon::parse($attendance->attendance_time)->format('Y-m-d');
            $lateThreshold = Carbon::parse($clockInDate . ' ' . $startTimeSetting);
            $restoredStatus = Carbon::parse($attendance->attendance_time)->gt($lateThreshold) ? 'terlambat' : 'tepat_waktu';

            $attendance->update(['status' => $restoredStatus]);

            $statusText = $restoredStatus === 'terlambat' ? ' (Terlambat Pagi)' : '';

            return response()->json([
                'status' => 'clock_in_from_permit',
                'message' => 'Telah Kembali ke Sekolah' . $statusText,
                'student_name' => $student->name,
                'time' => now()->format('H:i:s'),
            ]);
        }

        // KASUS 2: Siswa akan izin keluar
        if (in_array($attendance->status, ['tepat_waktu', 'terlambat'])) {
            if (!$request->filled('reason')) {
                return response()->json([
                    'status' => 'reason_required',
                    'message' => 'Alasan wajib diisi.',
                    'student_name' => $student->name,
                ], 422);
            }

            StudentPermit::create([
                'student_id'          => $student->id,
                'attendance_id'       => $attendance->id,
                'reason'              => $request->reason,
                'status'              => 'checked_out',
                'security_user_id'    => $request->user()?->id,
                'security_name'       => $request->user()?->name,
                'time_out'            => now(),
            ]);

            $attendance->update(['status' => 'izin_keluar']);

            return response()->json([
                'status' => 'permit_granted',
                'message' => 'Izin Keluar Telah Dicatat',
                'student_name' => $student->name,
                'time' => now()->format('H:i:s'),
            ]);
        }

        return response()->json([
            'status' => 'invalid_status',
            'message' => 'Status siswa saat ini tidak memungkinkan untuk izin. Status: ' . $attendance->status,
            'student_name' => $student->name,
        ], 409);
    }

    /**
     * Menghitung jarak antara dua titik koordinat GPS.
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
