<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\Notification\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal jika terjadi kegagalan jaringan
     */
    public int $tries = 3;

    /**
     * Timeout job dalam detik
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $type,
        public int $studentId,
        public ?int $attendanceId = null,
        public ?string $date = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppNotificationService $waService): void
    {
        $student = Student::with('parents')->find($this->studentId);
        if (!$student) {
            Log::warning("[SendWhatsAppNotificationJob] Siswa dengan ID {$this->studentId} tidak ditemukan.");
            return;
        }

        try {
            switch ($this->type) {
                case 'clock_in':
                    if ($this->attendanceId) {
                        $attendance = Attendance::find($this->attendanceId);
                        if ($attendance) {
                            $waService->sendClockInNotification($student, $attendance);
                        }
                    }
                    break;

                case 'clock_out':
                    if ($this->attendanceId) {
                        $attendance = Attendance::find($this->attendanceId);
                        if ($attendance) {
                            $waService->sendClockOutNotification($student, $attendance);
                        }
                    }
                    break;

                case 'absent':
                case 'alpa':
                    $dateObj = $this->date ? Carbon::parse($this->date) : now();
                    $waService->sendAbsentNotification($student, $dateObj);
                    break;

                default:
                    Log::warning("[SendWhatsAppNotificationJob] Tipe notifikasi '{$this->type}' tidak dikenali.");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("[SendWhatsAppNotificationJob] Gagal mengirim notifikasi: " . $e->getMessage());
            throw $e;
        }
    }
}
