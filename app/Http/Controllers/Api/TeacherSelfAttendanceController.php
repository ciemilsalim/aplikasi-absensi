<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeacherAttendance;
use App\Models\Setting;
use App\Traits\GpsValidationTrait;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TeacherSelfAttendanceController extends Controller
{
    use GpsValidationTrait;

    /**
     * Get attendance status for the teacher today.
     */
    public function getStatus(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        $today = Carbon::today();
        $attendance = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('created_at', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_clocked_in'     => $attendance != null,
                'is_checked_out'     => ($attendance && $attendance->checkout_time) ? true : false,
                'attendance_data'    => $attendance,
                'is_face_registered' => !empty($teacher->photo),
            ]
        ]);
    }

    /**
     * Store teacher self-attendance.
     */
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|string', // Base64
        ]);

        $teacher = $request->user()->teacher;
        $today = Carbon::today();

        // 1. Cek Hari Libur & Akhir Pekan
        if ($today->isWeekend()) {
            return response()->json(['status' => 'error', 'message' => 'Absensi tidak tersedia di akhir pekan.'], 422);
        }

        // 2. Cek apakah sudah absen
        $exists = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('created_at', $today)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah melakukan absensi hari ini.'], 422);
        }

        // 3. Validasi GPS
        $gpsValidation = $this->validateGps($request->latitude, $request->longitude);
        if (!$gpsValidation['isValid']) {
            return response()->json(['status' => 'error', 'message' => $gpsValidation['message']], 422);
        }

        // 4. Save Photo Evidence
        $image = $request->photo;
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'teacher_selfie_' . $teacher->id . '_' . time() . '.png';
        $path = 'teacher_attendances/' . $imageName;
        Storage::disk('public')->put($path, base64_decode($image));

        // 5. Create Record
        $attendance = TeacherAttendance::create([
            'teacher_id' => $teacher->id,
            'status' => 'hadir',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_evidence' => $path,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi datang berhasil dicatat!',
            'data' => $attendance
        ]);
    }

    /**
     * Store teacher self-attendance (Clock Out).
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|string', // Base64
        ]);

        $teacher = $request->user()->teacher;
        $today = Carbon::today();

        // 1. Cek Record Hari Ini
        $attendance = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('created_at', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['status' => 'error', 'message' => 'Anda belum melakukan absensi datang hari ini.'], 422);
        }

        if ($attendance->checkout_time) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah melakukan absensi pulang hari ini.'], 422);
        }

        // 2. Validasi GPS
        $gpsValidation = $this->validateGps($request->latitude, $request->longitude);
        if (!$gpsValidation['isValid']) {
            return response()->json(['status' => 'error', 'message' => $gpsValidation['message']], 422);
        }

        // 3. Save Photo Evidence
        $image = $request->photo;
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'teacher_checkout_' . $teacher->id . '_' . time() . '.png';
        $path = 'teacher_attendances/' . $imageName;
        Storage::disk('public')->put($path, base64_decode($image));

        // 4. Update Record
        $attendance->update([
            'checkout_time'           => now(),
            'checkout_latitude'       => $request->latitude,
            'checkout_longitude'      => $request->longitude,
            'checkout_photo_evidence' => $path,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi pulang berhasil dicatat!',
            'data' => $attendance
        ]);
    }

    /**
     * API for Face Registration (Individual).
     */
    public function registerFace(Request $request)
    {
        $request->validate([
            'photo' => 'required|string', // Base64
        ]);

        $teacher = $request->user()->teacher;
        $image = $request->photo;
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'teacher_profile_' . $teacher->id . '_' . time() . '.png';
        $path = 'teachers/photos/' . $imageName;

        if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
            Storage::disk('public')->delete($teacher->photo);
        }

        Storage::disk('public')->put($path, base64_decode($image));
        $teacher->update(['photo' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Foto wajah berhasil didaftarkan!',
            'photo_url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Get attendance history for the teacher.
     */
    public function getHistory(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        $history = TeacherAttendance::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->limit($request->query('limit', 30))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}
