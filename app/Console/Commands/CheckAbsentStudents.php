<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\Notification;
use Carbon\Carbon;

class CheckAbsentStudents extends Command
{
    protected $signature = 'attendance:check-absent';
    protected $description = 'Cek siswa aktif yang tidak hadir tanpa keterangan dan tandai alpa / izin otomatis.';

    public function handle()
    {
        $settings = Setting::pluck('value', 'key');
        $today = Carbon::today();

        // 1. Cek Akhir Pekan (Sabtu & Minggu)
        if ($today->isWeekend()) {
            $this->info('Hari ini akhir pekan (Sabtu/Minggu). Pengecekan alpa dilewati.');
            return 0;
        }

        // 2. Cek Kalender Pendidikan (Hari Libur & Belajar Mandiri)
        $holiday = \App\Models\Calendar::where(function ($q) {
                $q->where('is_holiday', true)->orWhere('is_self_study', true);
            })
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })->first();

        if ($holiday) {
            $this->info('Hari ini terdaftar sebagai: ' . $holiday->title . '. Pengecekan alpa dilewati.');
            return 0;
        }

        // Siswa yang sudah memiliki catatan kehadiran hari ini (masuk, pulang, izin, sakit, alpa)
        $recordedStudentIds = Attendance::whereDate('attendance_time', $today)->pluck('student_id')->toArray();

        // Ambil hanya siswa AKTIF yang belum memiliki catatan kehadiran hari ini
        $activeUnrecordedStudents = Student::active()
            ->whereNotIn('id', $recordedStudentIds)
            ->with(['parents.user'])
            ->get();

        if ($activeUnrecordedStudents->isEmpty()) {
            $this->info('Semua siswa aktif telah memiliki catatan presensi hari ini.');
            return 0;
        }

        $sendNotification = ($settings->get('send_absent_notification', 'off') === 'on');
        $alpaCount = 0;
        $excusedCount = 0;

        foreach ($activeUnrecordedStudents as $student) {
            // Cek apakah ada pengajuan izin/sakit yang sudah disetujui (LeaveRequest approved)
            $approvedLeave = LeaveRequest::where('student_id', $student->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($approvedLeave) {
                $status = in_array($approvedLeave->type, ['sakit', 'izin']) ? $approvedLeave->type : 'izin';
                Attendance::create([
                    'student_id' => $student->id,
                    'attendance_time' => $today->copy()->startOfDay(),
                    'status' => $status,
                ]);
                $excusedCount++;
                continue;
            }

            // Jika tidak ada izin/sakit yang disetujui, catat sebagai ALPA
            Attendance::create([
                'student_id' => $student->id,
                'attendance_time' => $today->copy()->startOfDay(),
                'status' => 'alpa',
            ]);
            $alpaCount++;

            // Kirim notifikasi jika fitur diaktifkan
            if ($sendNotification) {
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

            // Kirim notifikasi WhatsApp ke orang tua jika diaktifkan
            if ($settings->get('wa_notify_absent', 'off') === 'on') {
                \App\Jobs\SendWhatsAppNotificationJob::dispatch('absent', $student->id, null, $today->toDateString());
            }
        }

        $this->info("Proses selesai: {$alpaCount} siswa dicatat alpa, {$excusedCount} siswa dicatat dari izin/sakit disetujui.");
        return 0;
    }
}
