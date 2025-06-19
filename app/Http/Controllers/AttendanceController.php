<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Menampilkan halaman utama dengan pemindai QR Code.
     *
     * @return \Illuminate\View\View
     */
    public function showScanner()
    {
        return view('scanner');
    }

    /**
     * Menyimpan atau memperbarui data absensi saat QR Code berhasil dipindai.
     * Logika ini menangani absen masuk dan absen pulang.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAttendance(Request $request)
    {
        // 1. Validasi input, pastikan ada ID unik dari QR Code
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
        ]);

        try {
            // 2. Cari siswa berdasarkan ID unik
            $student = Student::where('unique_id', $request->student_unique_id)->firstOrFail();
            $today = Carbon::today();

            // 3. Cari data absensi untuk siswa ini pada hari ini
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->first();

            // 4. Logika Absensi:
            //    - Jika belum ada data sama sekali, catat sebagai ABSEN MASUK.
            if (!$attendance) {
                $newAttendance = Attendance::create([
                    'student_id'      => $student->id,
                    'attendance_time' => now(),
                ]);

                return response()->json([
                    'status'        => 'clock_in', // Status untuk absen masuk
                    'message'       => 'Kehadiran berhasil dicatat!',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                    'time'          => $newAttendance->attendance_time->format('H:i:s')
                ]);
            }

            //    - Jika sudah ada data masuk tapi jam pulang masih kosong, catat sebagai ABSEN PULANG.
            if ($attendance && is_null($attendance->checkout_time)) {
                // **PERBAIKAN DI SINI**
                // Perbarui objek terlebih dahulu, lalu simpan.
                // Ini memastikan $attendance->checkout_time tidak null saat dipanggil.
                $attendance->checkout_time = now();
                $attendance->save();
                
                return response()->json([
                    'status'        => 'clock_out', // Status untuk absen pulang
                    'message'       => 'Absen pulang berhasil dicatat!',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                    'time'          => $attendance->checkout_time->format('H:i:s')
                ]);
            }

            //    - Jika sudah absen masuk dan pulang, berikan pesan bahwa absensi sudah selesai.
            if ($attendance && !is_null($attendance->checkout_time)) {
                return response()->json([
                    'status'        => 'completed', // Status untuk selesai
                    'message'       => 'Anda sudah menyelesaikan absensi hari ini.',
                    'student_name'  => $student->name,
                    'student_nis'   => $student->nis,
                ], 409); // 409 Conflict menunjukkan permintaan tidak dapat diproses karena sudah selesai.
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            // Tangani semua error tak terduga lainnya
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.'], 500);
        }
    }
}
