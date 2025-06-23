<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\Setting; // Impor model Setting

class AttendanceController extends Controller
{
    public function showScanner()
    {
        return view('scanner');
    }

    public function storeAttendance(Request $request)
    {
        $request->validate(['student_unique_id' => 'required|string|exists:students,unique_id']);

        try {
            $student = Student::where('unique_id', $request->student_unique_id)->firstOrFail();
            $now = now();
            $today = $now->copy()->startOfDay();

            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->first();

            // **LOGIKA BARU**: Cek jika siswa sudah tercatat izin atau sakit hari ini
            if ($attendance && in_array($attendance->status, ['izin', 'sakit'])) {
                return response()->json([
                    'status'        => 'on_leave', // Status baru untuk menandakan siswa sedang izin/sakit
                    'message'       => 'Anda sudah tercatat ' . $attendance->status . ' hari ini dan tidak dapat melakukan absensi.',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                ], 409); // 409 Conflict, karena aksi tidak dapat diproses.
            }

            // KASUS 1: Absen Masuk
           if (!$attendance) {
                // Ambil batas waktu dari database, dengan nilai default jika tidak ada
                $batasWaktuMasuk = Setting::where('key', 'jam_masuk')->first()->value ?? '07:30';
                list($hour, $minute) = explode(':', $batasWaktuMasuk);
                $lateTime = $today->copy()->setTime($hour, $minute, 0);
                
                $status = ($now->gt($lateTime)) ? 'terlambat' : 'tepat_waktu';

                $newAttendance = Attendance::create([
                    'student_id'      => $student->id,
                    'attendance_time' => $now,
                    'status'          => $status,
                ]);

                return response()->json([
                    'status'            => 'clock_in',
                    'attendance_status' => $status,
                    'message'           => 'Kehadiran berhasil dicatat!',
                    'student_name'      => $student->name,
                    'student_nis'       => $student->nis,
                    'time'              => $newAttendance->attendance_time->format('H:i:s')
                ]);
            }

            // KASUS 2: Absen Pulang
            if ($attendance && is_null($attendance->checkout_time)) {
                $attendance->update(['checkout_time' => $now]);
                
                return response()->json([
                    'status'        => 'clock_out',
                    'message'       => 'Absen pulang berhasil dicatat!',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                    'time'          => $attendance->checkout_time->format('H:i:s')
                ]);
            }

            // KASUS 3: Sudah selesai
            if ($attendance && !is_null($attendance->checkout_time)) {
                return response()->json([
                    'status'        => 'completed',
                    'message'       => 'Anda sudah menyelesaikan absensi hari ini.',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                ], 409);
            }

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.'], 500);
        }
    }
}
