<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller; // Ditambahkan untuk memperbaiki error
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Menampilkan halaman utama dengan pemindai QR Code.
     */
    public function showScanner()
    {
        return view('scanner');
    }

    /**
     * Menampilkan daftar siswa dan QR Code mereka.
     */
    public function showStudents()
    {
        $students = Student::orderBy('name')->get();
        return view('students', ['students' => $students]);
    }

    /**
     * Menyimpan data absensi saat QR Code berhasil dipindai.
     */
    public function storeAttendance(Request $request)
    {
        // Validasi request, pastikan student_unique_id ada
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
        ]);

        try {
            // Cari siswa berdasarkan unique_id
            $student = Student::where('unique_id', $request->student_unique_id)->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Siswa tidak ditemukan.'
                ], 404);
            }

            // Cek apakah siswa sudah absen hari ini
            $today = Carbon::today();
            $alreadyAttended = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->exists();

            if ($alreadyAttended) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Anda sudah tercatat hadir hari ini.',
                    'student_name' => $student->name,
                ], 409); // 409 Conflict
            }

            // Jika belum, simpan data absensi baru
            $attendance = new Attendance();
            $attendance->student_id = $student->id;
            $attendance->attendance_time = now();
            $attendance->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Kehadiran berhasil dicatat!',
                'student_name' => $student->name,
                'time' => $attendance->attendance_time->format('H:i:s')
            ]);

        } catch (\Exception $e) {
            // Tangani error jika terjadi
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada server.',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }
}
