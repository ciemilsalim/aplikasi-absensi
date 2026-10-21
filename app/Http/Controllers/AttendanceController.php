<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\ScanLog;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function showScanner()
    {
        $students = Student::active()
            ->select('id', 'unique_id', 'name', 'photo', 'face_descriptor')
            ->whereNotNull('photo')
            ->get()
            ->map(function ($student) {
            return [
            'unique_id' => $student->unique_id,
            'name' => $student->name,
            'photo_url' => asset('storage/' . $student->photo),
            'face_descriptor' => $student->face_descriptor,
            ];
        });

        return view('scanner', compact('students'));
    }

    public function storeAttendance(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'scanned_at' => 'nullable|date',
        ]);

        $scanner = auth()->user();
        $settings = Setting::pluck('value', 'key');
        $appTimezone = config('app.timezone', 'Asia/Makassar');
        $scannedAt = $request->filled('scanned_at')
            ? Carbon::parse($request->scanned_at)->setTimezone($appTimezone)
            : now($appTimezone);

        $result = $this->processSingleAttendance(
            $request->student_unique_id,
            (float) $request->latitude,
            (float) $request->longitude,
            $scannedAt,
            $scanner,
            $settings
        );

        return response()->json($result['body'], $result['code']);
    }

    /**
     * Sinkronisasi antrean pemindaian offline dari kiosk (PWA / IndexedDB)
     */
    public function syncOffline(Request $request)
    {
        $validated = $request->validate([
            'scans' => 'required|array|min:1|max:200',
            'scans.*.student_unique_id' => 'required|string',
            'scans.*.latitude' => 'required|numeric',
            'scans.*.longitude' => 'required|numeric',
            'scans.*.scanned_at' => 'nullable|date',
            'scans.*.client_uuid' => 'nullable|string',
        ]);

        $scanner = auth()->user();
        $settings = Setting::pluck('value', 'key');

        $synced = 0;
        $failed = 0;
        $results = [];

        $appTimezone = config('app.timezone', 'Asia/Makassar');

        foreach ($validated['scans'] as $scanItem) {
            $scannedAt = !empty($scanItem['scanned_at'])
                ? Carbon::parse($scanItem['scanned_at'])->setTimezone($appTimezone)
                : now($appTimezone);
            $clientUuid = $scanItem['client_uuid'] ?? null;

            try {
                $processResult = $this->processSingleAttendance(
                    $scanItem['student_unique_id'],
                    (float) $scanItem['latitude'],
                    (float) $scanItem['longitude'],
                    $scannedAt,
                    $scanner,
                    $settings
                );

                $isSuccess = ($processResult['code'] === 200);
                if ($isSuccess) {
                    $synced++;
                } else {
                    $failed++;
                }

                $results[] = [
                    'client_uuid' => $clientUuid,
                    'student_unique_id' => $scanItem['student_unique_id'],
                    'success' => $isSuccess,
                    'code' => $processResult['code'],
                    'response' => $processResult['body'],
                ];
            } catch (\Exception $e) {
                $failed++;
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'student_unique_id' => $scanItem['student_unique_id'],
                    'success' => false,
                    'code' => 500,
                    'response' => [
                        'status' => 'error',
                        'message' => 'Gagal memproses item antrean offline.',
                    ],
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Sinkronisasi selesai: {$synced} berhasil diproses, {$failed} ditolak/gagal.",
            'synced_count' => $synced,
            'failed_count' => $failed,
            'total' => count($validated['scans']),
            'results' => $results,
        ]);
    }

    /**
     * Memproses satu data pemindaian absensi siswa.
     */
    protected function processSingleAttendance(
        string $qrData,
        float $latitude,
        float $longitude,
        Carbon $scannedAt,
        $scanner = null,
        $settings = null
    ): array {
        if (!$settings) {
            $settings = Setting::pluck('value', 'key');
        }

        $scannerName = $scanner ? $scanner->name : 'Sistem';
        $scannerRole = $scanner ? ($scanner->role ?? 'unknown') : 'unknown';

        try {
            // Format gabungan: NIS-UNIQUE_ID
            // UUID length is 36. We check if the string has NIS- prefix before UUID
            if (strlen($qrData) > 37 && substr($qrData, -37, 1) === '-') {
                $nis = substr($qrData, 0, -37);
                $uniqueId = substr($qrData, -36);
                $student = Student::where('nis', $nis)->where('unique_id', $uniqueId)->first();
            } else {
                // Format lama atau manual (hanya UUID atau hanya NIS)
                $student = Student::where('unique_id', $qrData)->orWhere('nis', $qrData)->first();
            }

            if (!$student) {
                ScanLog::create([
                    'user_id'        => $scanner?->id,
                    'user_name'      => $scannerName,
                    'user_role'      => $scannerRole,
                    'scan_type'      => 'gagal',
                    'failure_reason' => 'Siswa tidak ditemukan (' . $qrData . ')',
                    'scanned_at'     => $scannedAt,
                ]);

                return [
                    'code' => 404,
                    'body' => [
                        'status' => 'not_found',
                        'message' => 'ID Siswa tidak ditemukan di database.',
                    ],
                ];
            }

            // Cek status keaktifan siswa
            if (in_array(strtolower(trim((string)($student->status ?? ''))), Student::$inactiveStatuses)) {
                ScanLog::create([
                    'user_id'        => $scanner?->id,
                    'user_name'      => $scannerName,
                    'user_role'      => $scannerRole,
                    'student_id'     => $student->id,
                    'student_name'   => $student->name,
                    'student_nis'    => $student->nis,
                    'scan_type'      => 'gagal',
                    'failure_reason' => 'Siswa tidak aktif',
                    'scanned_at'     => $scannedAt,
                ]);
                return [
                    'code' => 403,
                    'body' => [
                        'status' => 'inactive_error',
                        'message' => 'Presensi ditolak. Siswa ' . $student->name . ' berstatus tidak aktif (' . ($student->status ?? 'nonaktif') . ').',
                        'student_name' => $student->name,
                    ],
                ];
            }

            $now = $scannedAt->copy();
            $today = $now->copy()->startOfDay();

            // == CEK HARI LIBUR & AKHIR PEKAN ==
            // 1. Cek Akhir Pekan (Sabtu & Minggu)
            if ($now->isWeekend()) {
                ScanLog::create([
                    'user_id'        => $scanner?->id,
                    'user_name'      => $scannerName,
                    'user_role'      => $scannerRole,
                    'student_id'     => $student->id,
                    'student_name'   => $student->name,
                    'student_nis'    => $student->nis,
                    'scan_type'      => 'gagal',
                    'failure_reason' => 'Hari akhir pekan',
                    'scanned_at'     => $scannedAt,
                ]);
                return [
                    'code' => 403,
                    'body' => [
                        'status' => 'holiday_error',
                        'message' => 'Absensi tidak dapat dilakukan pada akhir pekan (Sabtu/Minggu).',
                        'student_name' => $student->name,
                    ],
                ];
            }

            // 2. Cek Kalender Pendidikan (Hari Libur)
            $holiday = \App\Models\Calendar::where('is_holiday', true)
                ->whereDate('start_date', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $today);
                })->first();

            if ($holiday) {
                ScanLog::create([
                    'user_id'        => $scanner?->id,
                    'user_name'      => $scannerName,
                    'user_role'      => $scannerRole,
                    'student_id'     => $student->id,
                    'student_name'   => $student->name,
                    'student_nis'    => $student->nis,
                    'scan_type'      => 'gagal',
                    'failure_reason' => 'Hari libur: ' . $holiday->title,
                    'scanned_at'     => $scannedAt,
                ]);
                return [
                    'code' => 403,
                    'body' => [
                        'status' => 'holiday_error',
                        'message' => 'Hari ini libur: ' . $holiday->title,
                        'student_name' => $student->name,
                    ],
                ];
            }

            // Validasi Jarak GPS
            $schoolLat = $settings->get('school_latitude');
            $schoolLng = $settings->get('school_longitude');
            $radius = $settings->get('attendance_radius', 100);
            $distance = $this->haversineDistance($latitude, $longitude, $schoolLat, $schoolLng);

            if ($distance > $radius) {
                ScanLog::create([
                    'user_id'        => $scanner?->id,
                    'user_name'      => $scannerName,
                    'user_role'      => $scannerRole,
                    'student_id'     => $student->id,
                    'student_name'   => $student->name,
                    'student_nis'    => $student->nis,
                    'scan_type'      => 'gagal',
                    'failure_reason' => 'Di luar radius (' . round($distance) . 'm)',
                    'scanned_at'     => $scannedAt,
                ]);
                return [
                    'code' => 403,
                    'body' => [
                        'status' => 'location_error',
                        'message' => "Anda berada di luar radius absensi. Jarak Anda: " . round($distance) . " meter dari sekolah.",
                        'student_name' => $student->name,
                    ],
                ];
            }

            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('attendance_time', $today)
                ->first();

            // Cek jika siswa sudah tercatat izin atau sakit
            if ($attendance && in_array($attendance->status, ['izin', 'sakit', 'alpa'])) {
                ScanLog::create([
                    'user_id'        => $scanner?->id,
                    'user_name'      => $scannerName,
                    'user_role'      => $scannerRole,
                    'student_id'     => $student->id,
                    'student_name'   => $student->name,
                    'student_nis'    => $student->nis,
                    'scan_type'      => 'gagal',
                    'failure_reason' => 'Sudah tercatat ' . $attendance->status,
                    'scanned_at'     => $scannedAt,
                ]);
                return [
                    'code' => 409,
                    'body' => [
                        'status' => 'on_leave',
                        'message' => 'Anda sudah tercatat ' . $attendance->status . ' hari ini dan tidak dapat melakukan absensi.',
                        'student_name' => $student->name,
                    ],
                ];
            }

            // KASUS 1: Siswa sudah pernah scan hari ini (sudah absen masuk)
            if ($attendance) {
                if (!is_null($attendance->checkout_time)) {
                    ScanLog::create([
                        'user_id'        => $scanner?->id,
                        'user_name'      => $scannerName,
                        'user_role'      => $scannerRole,
                        'student_id'     => $student->id,
                        'student_name'   => $student->name,
                        'student_nis'    => $student->nis,
                        'scan_type'      => 'gagal',
                        'failure_reason' => 'Absensi hari ini sudah selesai',
                        'scanned_at'     => $scannedAt,
                    ]);
                    return [
                        'code' => 409,
                        'body' => [
                            'status' => 'completed',
                            'message' => 'Anda sudah menyelesaikan absensi hari ini.',
                            'student_name' => $student->name,
                        ],
                    ];
                }

                $jamPulangSetting = $settings->get('jam_pulang', '16:00');
                $waktuPulang = $today->copy()->setTimeFromTimeString($jamPulangSetting);

                // Proteksi jeda minimal: Siswa tidak dapat absen pulang jika baru absen masuk kurang dari 15 menit
                $clockInTime = $attendance->attendance_time ? Carbon::parse($attendance->attendance_time)->setTimezone($now->getTimezone()) : null;
                $minutesSinceClockIn = $clockInTime ? $clockInTime->diffInMinutes($now, false) : 999;
                if ($minutesSinceClockIn >= 0 && $minutesSinceClockIn < 15) {
                    ScanLog::create([
                        'user_id'        => $scanner?->id,
                        'user_name'      => $scannerName,
                        'user_role'      => $scannerRole,
                        'student_id'     => $student->id,
                        'student_name'   => $student->name,
                        'student_nis'    => $student->nis,
                        'scan_type'      => 'gagal',
                        'failure_reason' => 'Scan berulang (baru saja absen masuk ' . round($minutesSinceClockIn) . ' menit lalu)',
                        'scanned_at'     => $scannedAt,
                    ]);
                    return [
                        'code' => 409,
                        'body' => [
                            'status' => 'already_clocked_in',
                            'message' => 'Anda sudah berhasil absen masuk. Absen pulang belum dapat dilakukan sesaat setelah absen masuk.',
                            'student_name' => $student->name,
                        ],
                    ];
                }

                if ($now->lt($waktuPulang)) {
                    ScanLog::create([
                        'user_id'        => $scanner?->id,
                        'user_name'      => $scannerName,
                        'user_role'      => $scannerRole,
                        'student_id'     => $student->id,
                        'student_name'   => $student->name,
                        'student_nis'    => $student->nis,
                        'scan_type'      => 'gagal',
                        'failure_reason' => 'Belum waktunya absen pulang (sebelum ' . $waktuPulang->format('H:i') . ')',
                        'scanned_at'     => $scannedAt,
                    ]);
                    return [
                        'code' => 409,
                        'body' => [
                            'status' => 'already_clocked_in',
                            'message' => 'Anda sudah absen masuk. Absen pulang baru bisa dilakukan setelah pukul ' . $waktuPulang->format('H:i') . '.',
                            'student_name' => $student->name,
                        ],
                    ];
                }

                $attendance->update(['checkout_time' => $now]);

                // Catat scan berhasil: pulang
                ScanLog::create([
                    'user_id'      => $scanner?->id,
                    'user_name'    => $scannerName,
                    'user_role'    => $scannerRole,
                    'student_id'   => $student->id,
                    'student_name' => $student->name,
                    'student_nis'  => $student->nis,
                    'scan_type'    => 'pulang',
                    'scanned_at'   => $scannedAt,
                ]);

                // Notifikasi WhatsApp ke orang tua
                if ($settings->get('wa_notify_clock_out', 'off') === 'on') {
                    \App\Jobs\SendWhatsAppNotificationJob::dispatch('clock_out', $student->id, $attendance->id);
                }

                return [
                    'code' => 200,
                    'body' => [
                        'status' => 'clock_out',
                        'student_name' => $student->name,
                        'student_nis' => $student->nis,
                        'time' => $now->format('H:i:s'),
                    ],
                ];
            }

            // KASUS 2: Siswa belum pernah scan sama sekali (proses absen masuk)
            $batasWaktuMasuk = $settings->get('jam_masuk', '07:30');
            $lateTime = $today->copy()->setTimeFromTimeString($batasWaktuMasuk);
            $status = ($now->gt($lateTime)) ? 'terlambat' : 'tepat_waktu';

            $newAttendance = Attendance::create([
                'student_id' => $student->id,
                'attendance_time' => $now,
                'status' => $status,
            ]);

            // Catat scan berhasil: masuk
            ScanLog::create([
                'user_id'      => $scanner?->id,
                'user_name'    => $scannerName,
                'user_role'    => $scannerRole,
                'student_id'   => $student->id,
                'student_name' => $student->name,
                'student_nis'  => $student->nis,
                'scan_type'    => 'masuk',
                'scanned_at'   => $scannedAt,
            ]);

            // Notifikasi WhatsApp ke orang tua
            if ($settings->get('wa_notify_clock_in', 'off') === 'on') {
                \App\Jobs\SendWhatsAppNotificationJob::dispatch('clock_in', $student->id, $newAttendance->id);
            }

            return [
                'code' => 200,
                'body' => [
                    'status' => 'clock_in',
                    'attendance_status' => $status,
                    'student_name' => $student->name,
                    'student_nis' => $student->nis,
                    'time' => $newAttendance->attendance_time->format('H:i:s'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'body' => [
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage(),
                ],
            ];
        }
    }

    public function saveStudentDescriptor(Request $request)
    {
        $request->validate([
            'unique_id' => 'required|string|exists:students,unique_id',
            'face_descriptor' => 'required|string', // JSON stringified array
        ]);

        try {
            $student = Student::where('unique_id', $request->unique_id)->firstOrFail();
            
            // Simpan descriptor ke database
            $student->update([
                'face_descriptor' => $request->face_descriptor
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pola wajah berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan pola wajah.'
            ], 500);
        }
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
