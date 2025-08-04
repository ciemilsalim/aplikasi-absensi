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
        return view('permit-scanner');
    }

    /**
     * Memproses scan untuk izin keluar atau kembali.
     */
    public function storePermit(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
            'reason' => 'nullable|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $student = Student::where('unique_id', $request->student_unique_id)->firstOrFail();
        $today = now()->startOfDay();

        // Validasi Jarak GPS
        $settings = Setting::pluck('value', 'key');
        $schoolLat = $settings->get('school_latitude');
        $schoolLng = $settings->get('school_longitude');
        $radius = $settings->get('attendance_radius', 100);
        $distance = $this->haversineDistance($request->latitude, $request->longitude, $schoolLat, $schoolLng);

        if ($distance > $radius) {
            return response()->json([
                'status' => 'location_error',
                'message' => "Anda berada di luar radius yang diizinkan (" . round($distance) . " meter dari sekolah).",
                'student_name' => $student->name
            ], 403);
        }

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
            $activePermit->update(['time_in' => now()]);
            $attendance->update(['status' => 'tepat_waktu']); // Asumsi kembali menjadi tepat waktu

            return response()->json([
                'status' => 'clock_in_from_permit',
                'message' => 'Telah Kembali ke Sekolah',
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
                'student_id' => $student->id,
                'attendance_id' => $attendance->id,
                'reason' => $request->reason,
                'time_out' => now(),
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
    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
