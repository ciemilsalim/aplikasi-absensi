<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Notification; // Impor model Notification
use Carbon\Carbon;

class CheckAbsentStudents extends Command
{
    protected $signature = 'attendance:check-absent';
    protected $description = 'Cek siswa yang tidak hadir tanpa keterangan dan buat notifikasi untuk orang tua.';

    public function handle()
    {
        $settings = Setting::pluck('value', 'key');
        
        if ($settings->get('send_absent_notification', 'off') !== 'on') {
            $this->info('Fitur notifikasi siswa alpa tidak aktif.');
            return 0;
        }

        $today = Carbon::today();
        $presentStudentIds = Attendance::whereDate('attendance_time', $today)->pluck('student_id')->toArray();
        $absentStudents = Student::whereNotIn('id', $presentStudentIds)->with('parents.user')->get();

        if ($absentStudents->isEmpty()) {
            $this->info('Tidak ada siswa yang alpa hari ini.');
            return 0;
        }

        $this->info("Ditemukan {$absentStudents->count()} siswa alpa. Memulai proses...");

        foreach ($absentStudents as $student) {
            Attendance::create([
                'student_id' => $student->id,
                'attendance_time' => $today->startOfDay(),
                'status' => 'alpa',
            ]);

            // Buat notifikasi untuk setiap orang tua yang terhubung
            foreach ($student->parents as $parent) {
                if ($parent->user) {
                    Notification::create([
                        'user_id' => $parent->user->id,
                        'title' => 'Informasi Kehadiran Ananda ' . $student->name,
                        'message' => "Kami informasikan bahwa hingga saat ini ananda {$student->name} belum tercatat kehadirannya di sekolah pada hari " . now()->translatedFormat('l, d F Y') . ". Mohon konfirmasinya.",
                    ]);
                    $this->info("Notifikasi untuk wali dari {$student->name} telah dibuat.");
                }
            }
        }

        $this->info('Proses pengecekan siswa alpa selesai.');
        return 0;
    }
}
