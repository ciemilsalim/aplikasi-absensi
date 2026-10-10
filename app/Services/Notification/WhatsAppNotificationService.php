<?php

namespace App\Services\Notification;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /**
     * Normalisasi nomor telepon ke format internasional (misal: 628123456789)
     */
    public function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Hapus karakter selain digit
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleaned)) {
            return null;
        }

        // Ubah 08xxx menjadi 628xxx
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Mengambil konfigurasi WhatsApp Gateway dari database settings
     */
    public function getConfig(): array
    {
        $settings = Setting::pluck('value', 'key');

        return [
            'provider' => $settings->get('wa_gateway_provider', 'log'), // fonnte, wablas, generic, log
            'token' => $settings->get('wa_gateway_token', env('WA_GATEWAY_TOKEN', '')),
            'url' => $settings->get('wa_gateway_url', env('WA_GATEWAY_URL', '')),
            'notify_clock_in' => $settings->get('wa_notify_clock_in', 'off') === 'on',
            'notify_clock_out' => $settings->get('wa_notify_clock_out', 'off') === 'on',
            'notify_absent' => $settings->get('wa_notify_absent', 'off') === 'on',
        ];
    }

    /**
     * Kirim notifikasi pesan teks ke nomor tujuan
     */
    public function sendMessage(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        if (!$normalizedPhone) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp tidak valid atau kosong.',
            ];
        }

        $config = $this->getConfig();
        $provider = strtolower($config['provider'] ?? 'log');
        $token = $config['token'] ?? '';
        $customUrl = $config['url'] ?? '';

        // Mode Simulasi / Log (default jika provider 'log' atau token kosong)
        if ($provider === 'log' || (empty($token) && $provider !== 'generic')) {
            Log::info("[WhatsApp Gateway Simulasi] Ke: {$normalizedPhone} | Pesan: {$message}");
            return [
                'success' => true,
                'provider' => 'log',
                'message' => "Pesan disimulasikan dan dicatat ke log (Provider: {$provider}).",
                'target' => $normalizedPhone,
            ];
        }

        try {
            if ($provider === 'fonnte') {
                $endpoint = !empty($customUrl) ? $customUrl : 'https://api.fonnte.com/send';
                $response = Http::timeout(8)
                    ->withHeaders(['Authorization' => $token])
                    ->post($endpoint, [
                        'target' => $normalizedPhone,
                        'message' => $message,
                    ]);

                $isOk = $response->successful();
                return [
                    'success' => $isOk,
                    'provider' => 'fonnte',
                    'status_code' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ];
            }

            if ($provider === 'wablas') {
                $endpoint = !empty($customUrl) ? $customUrl : 'https://phone.wablas.com/api/send-message';
                $response = Http::timeout(8)
                    ->withHeaders(['Authorization' => $token])
                    ->post($endpoint, [
                        'phone' => $normalizedPhone,
                        'message' => $message,
                    ]);

                $isOk = $response->successful();
                return [
                    'success' => $isOk,
                    'provider' => 'wablas',
                    'status_code' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ];
            }

            if ($provider === 'generic') {
                if (empty($customUrl)) {
                    return [
                        'success' => false,
                        'message' => 'URL Endpoint Webhook Generic belum dikonfigurasi.',
                    ];
                }

                $request = Http::timeout(8);
                if (!empty($token)) {
                    $request = $request->withToken($token);
                }

                $response = $request->post($customUrl, [
                    'phone' => $normalizedPhone,
                    'message' => $message,
                    'timestamp' => now()->toISOString(),
                ]);

                $isOk = $response->successful();
                return [
                    'success' => $isOk,
                    'provider' => 'generic',
                    'status_code' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                ];
            }

            return [
                'success' => false,
                'message' => "Provider WhatsApp '{$provider}' tidak didukung.",
            ];
        } catch (\Exception $e) {
            Log::error("[WhatsApp Gateway Error] Gagal mengirim pesan ke {$normalizedPhone}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal menghubungi WhatsApp Gateway: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim notifikasi WhatsApp ke seluruh wali murid yang terhubung dengan siswa
     */
    public function sendToStudentParents(Student $student, string $message): array
    {
        $student->loadMissing('parents');
        $results = [];

        foreach ($student->parents as $parent) {
            if (!empty($parent->phone_number)) {
                $res = $this->sendMessage($parent->phone_number, $message);
                $results[] = [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'phone' => $parent->phone_number,
                    'result' => $res,
                ];
            }
        }

        return $results;
    }

    /**
     * Format & kirim notifikasi absensi masuk
     */
    public function sendClockInNotification(Student $student, Attendance $attendance): array
    {
        $config = $this->getConfig();
        if (!$config['notify_clock_in']) {
            return ['status' => 'skipped', 'reason' => 'Notifikasi WA absen masuk dinonaktifkan.'];
        }

        $time = $attendance->attendance_time ? Carbon::parse($attendance->attendance_time)->format('H:i') : now()->format('H:i');
        $statusLabel = $attendance->status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu';
        $schoolName = Setting::where('key', 'school_name')->value('value') ?? 'Sekolah';

        $message = "📢 *Informasi Kehadiran Siswa - {$schoolName}*\n\n" .
            "Yth. Orang Tua / Wali Murid,\n" .
            "Diberitahukan bahwa ananda:\n" .
            "• *Nama:* {$student->name}\n" .
            "• *NIS:* {$student->nis}\n" .
            "• *Waktu Masuk:* {$time} WITA\n" .
            "• *Status:* {$statusLabel}\n\n" .
            "Telah hadir dan melakukan presensi di sekolah hari ini. Terima kasih.";

        return $this->sendToStudentParents($student, $message);
    }

    /**
     * Format & kirim notifikasi absensi pulang
     */
    public function sendClockOutNotification(Student $student, Attendance $attendance): array
    {
        $config = $this->getConfig();
        if (!$config['notify_clock_out']) {
            return ['status' => 'skipped', 'reason' => 'Notifikasi WA absen pulang dinonaktifkan.'];
        }

        $time = $attendance->checkout_time ? Carbon::parse($attendance->checkout_time)->format('H:i') : now()->format('H:i');
        $schoolName = Setting::where('key', 'school_name')->value('value') ?? 'Sekolah';

        $message = "🏠 *Informasi Kepulangan Siswa - {$schoolName}*\n\n" .
            "Yth. Orang Tua / Wali Murid,\n" .
            "Diberitahukan bahwa ananda:\n" .
            "• *Nama:* {$student->name}\n" .
            "• *NIS:* {$student->nis}\n" .
            "• *Waktu Pulang:* {$time} WITA\n\n" .
            "Telah selesai mengikuti kegiatan belajar dan melakukan presensi pulang sekolah. Terima kasih.";

        return $this->sendToStudentParents($student, $message);
    }

    /**
     * Format & kirim notifikasi siswa tidak hadir / alpa
     */
    public function sendAbsentNotification(Student $student, ?Carbon $date = null): array
    {
        $config = $this->getConfig();
        if (!$config['notify_absent']) {
            return ['status' => 'skipped', 'reason' => 'Notifikasi WA siswa alpa dinonaktifkan.'];
        }

        $dateFormatted = ($date ?? now())->translatedFormat('l, d F Y');
        $schoolName = Setting::where('key', 'school_name')->value('value') ?? 'Sekolah';

        $message = "⚠️ *Pemberitahuan Ketidakhadiran - {$schoolName}*\n\n" .
            "Yth. Orang Tua / Wali Murid,\n" .
            "Kami menginformasikan bahwa hingga saat ini:\n" .
            "• *Nama:* {$student->name}\n" .
            "• *NIS:* {$student->nis}\n" .
            "• *Tanggal:* {$dateFormatted}\n\n" .
            "*Belum tercatat kehadirannya* di sekolah tanpa surat keterangan (Alpa).\n\n" .
            "Mohon segera mengonfirmasi status kehadiran ananda kepada wali kelas atau melalui aplikasi SIASEK. Terima kasih.";

        return $this->sendToStudentParents($student, $message);
    }

    /**
     * Menguji koneksi WhatsApp Gateway dengan nomor tujuan tes
     */
    public function testConnection(string $targetPhone): array
    {
        $schoolName = Setting::where('key', 'school_name')->value('value') ?? 'SIASEK';
        $testMessage = "✅ *Uji Coba WhatsApp Gateway SIASEK*\n\n" .
            "Pesan ini adalah uji coba koneksi gateway WhatsApp dari sistem *{$schoolName}*.\n" .
            "Waktu Uji: " . now()->format('d/m/Y H:i:s') . "\n" .
            "Status: Koneksi Berhasil!";

        return $this->sendMessage($targetPhone, $testMessage);
    }
}
