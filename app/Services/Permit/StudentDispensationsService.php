<?php

namespace App\Services\Permit;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\Setting;
use App\Models\User;
use App\Jobs\SendWhatsAppNotificationJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentDispensationsService
{
    /**
     * Menerbitkan satu atau beberapa surat izin e-dispensasi keluar oleh Guru Piket / Wali Kelas / Admin.
     */
    public function createDispensations(array $data, User $issuer): Collection
    {
        $studentIds = is_array($data['student_ids']) ? $data['student_ids'] : [$data['student_ids']];
        $today = Carbon::today();
        $createdPermits = collect();

        foreach ($studentIds as $studentId) {
            $student = Student::with('schoolClass', 'parents.user')->find($studentId);
            if (!$student) {
                continue;
            }

            // Cari absensi masuk hari ini, atau buatkan jika belum tercatat
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->first();

            if (!$attendance) {
                $attendance = Attendance::create([
                    'student_id'      => $student->id,
                    'attendance_time' => $today->copy()->setTime(7, 15, 0),
                    'status'          => 'tepat_waktu',
                ]);
            }

            // Generate Pass Code unik 6 digit (contoh: PASS-892147)
            do {
                $passCode = 'PASS-' . strtoupper(Str::random(6));
            } while (StudentPermit::where('pass_code', $passCode)->exists());

            $validUntil = !empty($data['valid_until']) ? Carbon::parse($data['valid_until']) : null;

            $permit = StudentPermit::create([
                'student_id'          => $student->id,
                'attendance_id'       => $attendance->id,
                'reason'              => $data['reason'],
                'permit_type'         => $data['permit_type'] ?? 'pulang_cepat',
                'pass_code'           => $passCode,
                'approved_by_user_id' => $issuer->id,
                'approved_by_name'    => $issuer->name,
                'status'              => 'approved', // Menunggu satpam memverifikasi di gerbang
                'time_out'            => now(), // Waktu terbit
                'valid_until'         => $validUntil,
                'notes'               => $data['notes'] ?? null,
            ]);

            if (!empty($data['notify_parent'])) {
                $this->dispatchWhatsAppAlert($student, $permit, 'terbit');
            }

            $createdPermits->push($permit);
        }

        return $createdPermits;
    }

    /**
     * Memverifikasi pemindaian kartu siswa atau barcode tiket di pos satpam gerbang.
     */
    public function verifyAndProcessScan(string $identifier, User $securityActor, ?string $fallbackReason = null): array
    {
        $today = Carbon::today();

        // 1. Cek apakah identifier adalah Pass Code e-Dispensasi (misal: PASS-XXXXXX)
        if (str_starts_with(strtoupper($identifier), 'PASS-')) {
            $permit = StudentPermit::with(['student.schoolClass', 'student.parents.user', 'attendance'])
                ->where('pass_code', strtoupper($identifier))
                ->whereDate('created_at', $today)
                ->first();

            if ($permit) {
                return $this->processPermitAction($permit, $securityActor);
            }
        }

        // 2. Lookup siswa berdasarkan NIS, UUID, atau format gabungan NIS-UUID
        $student = $this->findStudentByIdentifier($identifier);
        if (!$student) {
            return [
                'status'  => 'not_found',
                'code'    => 404,
                'message' => 'Data siswa tidak ditemukan dalam sistem.',
            ];
        }

        // 3. Cari izin aktif hari ini untuk siswa ini
        $activePermit = StudentPermit::with(['attendance', 'student.parents.user'])
            ->where('student_id', $student->id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['pending_approval', 'approved', 'checked_out'])
            ->latest('id')
            ->first();

        if ($activePermit) {
            return $this->processPermitAction($activePermit, $securityActor);
        }

        // 4. Jika TIDAK ADA e-dispensasi yang diterbitkan oleh Guru Piket:
        // Cek apakah ada alasan darurat dari satpam
        if (!empty($fallbackReason)) {
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->first();

            if (!$attendance) {
                $attendance = Attendance::create([
                    'student_id'      => $student->id,
                    'attendance_time' => $today->copy()->setTime(7, 15, 0),
                    'status'          => 'tepat_waktu',
                ]);
            }

            do {
                $passCode = 'PASS-' . strtoupper(Str::random(6));
            } while (StudentPermit::where('pass_code', $passCode)->exists());

            $newPermit = StudentPermit::create([
                'student_id'          => $student->id,
                'attendance_id'       => $attendance->id,
                'reason'              => $fallbackReason,
                'permit_type'         => 'pulang_cepat',
                'pass_code'           => $passCode,
                'approved_by_user_id' => $securityActor->id,
                'approved_by_name'    => $securityActor->name . ' (Satpam)',
                'security_user_id'    => $securityActor->id,
                'security_name'       => $securityActor->name,
                'status'              => 'approved',
                'time_out'            => now(),
                'checkout_time'       => now(),
            ]);

            $attendance->update(['status' => 'izin_keluar']);

            // WhatsApp notifikasi darurat
            $this->dispatchWhatsAppAlert($student, $newPermit, 'keluar');

            return [
                'success'         => true,
                'action'          => 'checkout',
                'is_dispensation' => false,
                'status'          => 'permit_granted',
                'code'            => 200,
                'message'         => 'Izin Keluar Darurat Dicatat oleh Satpam',
                'student_name'    => $student->name,
                'permit_type'     => $newPermit->permit_type_label,
                'pass_code'       => $newPermit->pass_code,
                'time'            => now()->format('H:i:s'),
            ];
        }

        return [
            'status'       => 'no_active_dispensation',
            'code'         => 422,
            'message'      => 'Siswa TIDAK memiliki surat e-dispensasi aktif dari Guru Piket / Wali Kelas.',
            'student_name' => $student->name,
            'student_id'   => $student->id,
        ];
    }

    /**
     * Memproses aksi keluar (checkout) atau kembali (checkin) pada e-dispensasi.
     */
    public function processPermitAction(StudentPermit $permit, User $securityActor): array
    {
        $student = $permit->student;
        $attendance = $permit->attendance ?: Attendance::where('student_id', $student->id)
            ->whereDate('attendance_time', Carbon::today())
            ->first();

        // KASUS B: Siswa sedang berada di luar sekolah dan sekarang kembali (Checkin kembali)
        if ($permit->status === 'checked_out' || ($permit->status === 'approved' && $permit->checkout_time !== null && $permit->checkin_time === null)) {
            $permit->update([
                'status'       => 'completed',
                'time_in'      => now(),
                'checkin_time' => now(),
            ]);

            // Pulihkan status kehadiran pagi hari
            if ($attendance) {
                $settings = Setting::pluck('value', 'key');
                $startTimeSetting = $settings->get('start_time', '07:15');
                $clockInDate = Carbon::parse($attendance->attendance_time)->format('Y-m-d');
                $lateThreshold = Carbon::parse($clockInDate . ' ' . $startTimeSetting);
                $restoredStatus = Carbon::parse($attendance->attendance_time)->gt($lateThreshold) ? 'terlambat' : 'tepat_waktu';
                $attendance->update(['status' => $restoredStatus]);
            }

            $this->dispatchWhatsAppAlert($student, $permit, 'kembali');

            return [
                'success'      => true,
                'action'       => 'checkin',
                'status'       => 'clock_in_from_permit',
                'code'         => 200,
                'message'      => 'Telah Kembali ke Sekolah (Izin Selesai)',
                'student_name' => $student->name,
                'permit_type'  => $permit->permit_type_label,
                'time'         => now()->format('H:i:s'),
            ];
        }

        // KASUS A: Siswa memegang izin yang SUDAH DISETUJUI, sekarang keluar gerbang (Checkout)
        if (in_array($permit->status, ['pending_approval', 'approved']) && $permit->checkout_time === null) {
            $permit->update([
                'status'           => 'approved',
                'security_user_id' => $securityActor->id,
                'security_name'    => $securityActor->name,
                'time_out'         => now(),
                'checkout_time'    => now(),
            ]);

            if ($attendance) {
                $attendance->update(['status' => 'izin_keluar']);
            }

            // Kirim notifikasi WA ke Orang Tua
            $this->dispatchWhatsAppAlert($student, $permit, 'keluar');

            return [
                'success'         => true,
                'action'          => 'checkout',
                'is_dispensation' => true,
                'status'          => 'permit_granted',
                'code'            => 200,
                'message'         => "Dispensasi Terverifikasi ({$permit->permit_type_label}) - Silakan Bukakan Gerbang",
                'student_name'    => $student->name,
                'permit_type'     => $permit->permit_type_label,
                'pass_code'       => $permit->pass_code,
                'reason'          => $permit->reason,
                'approver'        => $permit->approved_by_name,
                'time'            => now()->format('H:i:s'),
            ];
        }

        return [
            'status'       => 'already_completed',
            'code'         => 400,
            'message'      => 'Dispensasi ini sudah selesai digunakan.',
            'student_name' => $student->name,
        ];
    }

    /**
     * Mengirim notifikasi WhatsApp ke wali murid saat anak keluar/kembali gerbang.
     */
    protected function dispatchWhatsAppAlert(Student $student, StudentPermit $permit, string $eventType): void
    {
        $settings = Setting::pluck('value', 'key');

        $phoneNumber = null;
        $parent = $student->parents()->whereNotNull('phone_number')->first();
        if ($parent && !empty($parent->phone_number)) {
            $phoneNumber = $parent->phone_number;
        } elseif (!empty($student->parent_phone)) {
            $phoneNumber = $student->parent_phone;
        }

        if (!$phoneNumber) {
            return;
        }

        $schoolName = $settings->get('school_name', config('app.name', 'SIASEK'));
        $timeStr = now()->format('H:i') . ' WITA';

        if ($eventType === 'terbit') {
            $message = "Pemberitahuan {$schoolName}:\n\n"
                     . "E-Dispensasi resmi telah diterbitkan untuk ananda *{$student->name}*.\n\n"
                     . "Jenis: *{$permit->permit_type_label}*\n"
                     . "Alasan: {$permit->reason}\n"
                     . "Kode Pass: *{$permit->pass_code}*\n"
                     . "Diterbitkan Oleh: {$permit->approved_by_name}\n\n"
                     . "Pass card digital siap diverifikasi petugas Satpam di pos gerbang sekolah.";
        } elseif ($eventType === 'keluar') {
            $message = "Pemberitahuan {$schoolName}:\n\n"
                     . "Ananda *{$student->name}* telah meninggalkan lingkungan sekolah pada pukul {$timeStr}.\n\n"
                     . "Jenis: *{$permit->permit_type_label}*\n"
                     . "Alasan: {$permit->reason}\n"
                     . "Pemberi Izin: {$permit->approved_by_name}\n"
                     . "Petugas Gerbang: {$permit->security_name}\n\n"
                     . "Terima kasih atas perhatian Bapak/Ibu.";
        } else {
            $message = "Pemberitahuan {$schoolName}:\n\n"
                     . "Ananda *{$student->name}* telah kembali ke lingkungan sekolah pada pukul {$timeStr}.\n\n"
                     . "Terima kasih.";
        }

        SendWhatsAppNotificationJob::dispatch(
            'dispensation_' . $eventType,
            $student->id,
            $permit->attendance_id
        );
    }

    /**
     * Membangun query daftar dispensasi dengan filter.
     */
    public function getDispensationsQuery(array $filters = []): Builder
    {
        $query = StudentPermit::with(['student.schoolClass', 'approver', 'security', 'attendance'])
            ->latest('id');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('pass_code', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('approved_by_name', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['permit_type'])) {
            $query->where('permit_type', $filters['permit_type']);
        }

        if (!empty($filters['school_class_id'])) {
            $query->whereHas('student', function ($sq) use ($filters) {
                $sq->where('school_class_id', $filters['school_class_id']);
            });
        }

        return $query;
    }

    /**
     * Mencari siswa dengan format UUID / NIS / Gabungan.
     */
    protected function findStudentByIdentifier(string $qrData): ?Student
    {
        if (strlen($qrData) > 37 && substr($qrData, -37, 1) === '-') {
            $nis = substr($qrData, 0, -37);
            $uniqueId = substr($qrData, -36);
            return Student::where('nis', $nis)->where('unique_id', $uniqueId)->first();
        }

        return Student::where('unique_id', $qrData)->orWhere('nis', $qrData)->first();
    }
}
