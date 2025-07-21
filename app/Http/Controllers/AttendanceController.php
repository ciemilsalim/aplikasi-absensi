<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function showScanner()
    {
        return view('scanner');
    }

    public function storeAttendance(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $student = Student::where('unique_id', $request->student_unique_id)->firstOrFail();
            $now = now();
            $today = $now->copy()->startOfDay();
            
            // PERBAIKAN: Mengambil semua pengaturan dari database
            $settings = Setting::pluck('value', 'key');

            // Validasi Jarak GPS
            $schoolLat = $settings->get('school_latitude');
            $schoolLng = $settings->get('school_longitude');
            $radius = $settings->get('attendance_radius', 100);
            $distance = $this->haversineDistance($request->latitude, $request->longitude, $schoolLat, $schoolLng);

            if ($distance > $radius) {
                return response()->json([
                    'status' => 'location_error',
                    'message' => "Anda berada di luar radius absensi. Jarak Anda: " . round($distance) . " meter dari sekolah.",
                    'student_name' => $student->name
                ], 403);
            }

            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->first();

            // Cek jika siswa sudah tercatat izin atau sakit
            if ($attendance && in_array($attendance->status, ['izin', 'sakit', 'alpa'])) {
                return response()->json([
                    'status'        => 'on_leave',
                    'message'       => 'Anda sudah tercatat ' . $attendance->status . ' hari ini dan tidak dapat melakukan absensi.',
                    'student_name'  => $student->name,
                ], 409);
            }

            // KASUS 1: Siswa sudah pernah scan hari ini (sudah absen masuk)
            if ($attendance) {
                if (!is_null($attendance->checkout_time)) {
                    return response()->json([
                        'status'        => 'completed',
                        'message'       => 'Anda sudah menyelesaikan absensi hari ini.',
                        'student_name'  => $student->name,
                    ], 409);
                }

                // PERBAIKAN: Menggunakan jam pulang dari database
                $jamPulangSetting = $settings->get('jam_pulang', '16:00');
                $waktuPulang = $today->copy()->setTimeFromTimeString($jamPulangSetting);

                if ($now->lt($waktuPulang)) {
                    return response()->json([
                        'status'        => 'already_clocked_in',
                        'message'       => 'Anda sudah absen masuk. Absen pulang baru bisa dilakukan setelah pukul ' . $waktuPulang->format('H:i') . '.',
                        'student_name'  => $student->name,
                    ], 409);
                }

                $attendance->update(['checkout_time' => $now]);
                return response()->json([
                    'status'        => 'clock_out',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                    'time'          => $now->format('H:i:s'),
                ]);
            }

            // KASUS 2: Siswa belum pernah scan sama sekali (proses absen masuk)
            // PERBAIKAN: Menggunakan jam masuk dari database untuk menentukan keterlambatan
            $batasWaktuMasuk = $settings->get('jam_masuk', '07:30');
            $lateTime = $today->copy()->setTimeFromTimeString($batasWaktuMasuk);
            $status = ($now->gt($lateTime)) ? 'terlambat' : 'tepat_waktu';

            $newAttendance = Attendance::create([
                'student_id'      => $student->id,
                'attendance_time' => $now,
                'status'          => $status,
            ]);

            return response()->json([
                'status'            => 'clock_in',
                'attendance_status' => $status,
                'student_name'      => $student->name,
                'student_nis'       => $student->nis,
                'time'              => $newAttendance->attendance_time->format('H:i:s'),
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.'], 500);
        }
    }
    
    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
