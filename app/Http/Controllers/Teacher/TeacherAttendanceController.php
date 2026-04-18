<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAttendance;
use App\Models\Setting;
use App\Traits\GpsValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    use GpsValidationTrait;
    public function index()
    {
        $teacher = Auth::user()->teacher;
        $attendances = $teacher->attendances()->orderBy('created_at', 'desc')->paginate(10);

        return view('teacher.attendance.index', compact('attendances'));
    }

    public function showScanner()
    {
        $teacher = Auth::user()->teacher;
        $settings = Setting::whereIn('key', ['school_latitude', 'school_longitude', 'attendance_radius'])->pluck('value', 'key');

        $hasPhoto = !empty($teacher->photo);
        $face_descriptor = $teacher->face_descriptor;

        return view('teacher.attendance.scanner', compact('teacher', 'settings', 'hasPhoto', 'face_descriptor'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'status' => 'required|in:hadir,izin,sakit,alpa',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|string', // Base64 string
        ]);

        $teacher = Auth::user()->teacher;

        // == CEK HARI LIBUR & AKHIR PEKAN ==
        $today = Carbon::today();

        // 1. Cek Akhir Pekan (Sabtu & Minggu)
        if ($today->isWeekend()) {
            return response()->json(['success' => false, 'message' => 'Absensi tidak dapat dilakukan pada akhir pekan (Sabtu/Minggu).'], 422);
        }

        // 2. Cek Kalender Pendidikan (Hari Libur)
        $holiday = \App\Models\Calendar::where('is_holiday', true)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })->first();

        if ($holiday) {
            return response()->json(['success' => false, 'message' => 'Hari ini libur: ' . $holiday->title], 422);
        }
        // ==================================

        // 1. Verify Location (Server-side check as backup/validation)
        $gpsValidation = $this->validateGps($request->latitude, $request->longitude);
        if (!$gpsValidation['isValid']) {
            return response()->json(['success' => false, 'message' => $gpsValidation['message']], 422);
        }

        // 2. Decode and Save Photo Evidence
        $image = $request->photo; // your base64 encoded
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'attendance_evidence_' . time() . '.png';
        $path = 'teacher_attendances/' . $imageName;
        Storage::disk('public')->put($path, base64_decode($image));

        // 3. Record Attendance
        TeacherAttendance::create([
            'teacher_id' => $teacher->id,
            'status' => $request->status,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_evidence' => $path,
        ]);

        return response()->json(['success' => true, 'message' => 'Absensi berhasil dicatat!']);
    }

    public function registerFace(Request $request)
    {
        $request->validate([
            'photo' => 'required|string', // Base64
            'face_descriptor' => 'nullable|string', // JSON stringified array
        ]);

        $teacher = Auth::user()->teacher;

        $image = $request->photo;
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'teacher_face_' . $teacher->id . '_' . time() . '.png';
        $path = 'teachers/photos/' . $imageName;

        // Delete old photo if exists
        if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
            Storage::disk('public')->delete($teacher->photo);
        }

        Storage::disk('public')->put($path, base64_decode($image));

        $teacher->update([
            'photo' => $path,
            'face_descriptor' => $request->face_descriptor
        ]);

        return response()->json(['success' => true, 'message' => 'Wajah berhasil didaftarkan!']);
    }
}
