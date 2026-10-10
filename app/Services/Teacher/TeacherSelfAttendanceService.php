<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Traits\GpsValidationTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TeacherSelfAttendanceService
{
    use GpsValidationTrait;

    /**
     * Get attendance status for a teacher today.
     */
    public function getTodayStatus(Teacher $teacher): array
    {
        $today = Carbon::today();
        $attendance = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('created_at', $today)
            ->first();

        return [
            'has_clocked_in'     => $attendance !== null,
            'is_checked_out'     => ($attendance && $attendance->checkout_time) ? true : false,
            'attendance_data'    => $attendance,
            'is_face_registered' => !empty($teacher->photo),
        ];
    }

    /**
     * Process teacher self-attendance clock-in with anti-fake GPS validation.
     */
    public function clockIn(Teacher $teacher, array $data, ?string $userAgent = null): TeacherAttendance
    {
        $today = Carbon::today();

        // 1. Cek Hari Libur & Akhir Pekan
        if ($today->isWeekend()) {
            throw ValidationException::withMessages([
                'attendance' => ['Absensi tidak tersedia di akhir pekan.'],
            ]);
        }

        // 2. Cek apakah sudah absen hari ini
        $exists = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('created_at', $today)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'attendance' => ['Anda sudah melakukan absensi hari ini.'],
            ]);
        }

        // 3. Validasi GPS & Anti-Fake GPS
        $accuracy = $data['accuracy'] ?? $data['gps_accuracy'] ?? null;
        $isMock = $data['is_mock_location'] ?? $data['is_mock'] ?? false;
        $clientTimestamp = $data['client_timestamp'] ?? null;

        $gpsValidation = $this->validateGps(
            $data['latitude'],
            $data['longitude'],
            $accuracy,
            $isMock,
            $clientTimestamp
        );

        if (!$gpsValidation['isValid']) {
            throw ValidationException::withMessages([
                'gps' => [$gpsValidation['message']],
            ]);
        }

        // 4. Simpan Foto Selfie
        $photoPath = $this->saveBase64Photo(
            $data['photo'],
            'teacher_selfie_' . $teacher->id . '_' . time() . '.png',
            'teacher_attendances'
        );

        // 5. Buat Record Kehadiran
        return TeacherAttendance::create([
            'teacher_id' => $teacher->id,
            'status' => 'hadir',
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'gps_accuracy' => $accuracy !== null ? (float) $accuracy : null,
            'is_mock_location' => (bool) $isMock,
            'device_info' => $userAgent ? substr($userAgent, 0, 255) : null,
            'photo_evidence' => $photoPath,
        ]);
    }

    /**
     * Process teacher self-attendance clock-out with anti-fake GPS validation.
     */
    public function clockOut(Teacher $teacher, array $data, ?string $userAgent = null): TeacherAttendance
    {
        $today = Carbon::today();

        // 1. Cek Record Hari Ini
        $attendance = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('created_at', $today)
            ->first();

        if (!$attendance) {
            throw ValidationException::withMessages([
                'attendance' => ['Anda belum melakukan absensi datang hari ini.'],
            ]);
        }

        if ($attendance->checkout_time) {
            throw ValidationException::withMessages([
                'attendance' => ['Anda sudah melakukan absensi pulang hari ini.'],
            ]);
        }

        // 2. Validasi GPS & Anti-Fake GPS
        $accuracy = $data['accuracy'] ?? $data['gps_accuracy'] ?? null;
        $isMock = $data['is_mock_location'] ?? $data['is_mock'] ?? false;
        $clientTimestamp = $data['client_timestamp'] ?? null;

        $gpsValidation = $this->validateGps(
            $data['latitude'],
            $data['longitude'],
            $accuracy,
            $isMock,
            $clientTimestamp
        );

        if (!$gpsValidation['isValid']) {
            throw ValidationException::withMessages([
                'gps' => [$gpsValidation['message']],
            ]);
        }

        // 3. Simpan Foto Selfie Pulang
        $photoPath = $this->saveBase64Photo(
            $data['photo'],
            'teacher_checkout_' . $teacher->id . '_' . time() . '.png',
            'teacher_attendances'
        );

        // 4. Update Record Kehadiran
        $attendance->update([
            'checkout_time' => now(),
            'checkout_latitude' => $data['latitude'],
            'checkout_longitude' => $data['longitude'],
            'checkout_gps_accuracy' => $accuracy !== null ? (float) $accuracy : null,
            'checkout_is_mock_location' => (bool) $isMock,
            'checkout_photo_evidence' => $photoPath,
        ]);

        return $attendance;
    }

    /**
     * Register individual teacher face profile photo.
     */
    public function registerFace(Teacher $teacher, string $base64Photo): string
    {
        $filename = 'teacher_profile_' . $teacher->id . '_' . time() . '.png';
        $path = 'teachers/photos/' . $filename;

        if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
            Storage::disk('public')->delete($teacher->photo);
        }

        $photoPath = $this->saveBase64Photo($base64Photo, $filename, 'teachers/photos');
        $teacher->update(['photo' => $photoPath]);

        return $photoPath;
    }

    /**
     * Get attendance history for a teacher.
     */
    public function getHistory(Teacher $teacher, int $limit = 30): Collection
    {
        return TeacherAttendance::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Helper to decode and store base64 image to public disk.
     */
    protected function saveBase64Photo(string $base64, string $filename, string $folder): string
    {
        $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $cleanBase64 = str_replace(' ', '+', $cleanBase64);
        $path = $folder . '/' . $filename;

        Storage::disk('public')->put($path, base64_decode($cleanBase64));

        return $path;
    }
}
