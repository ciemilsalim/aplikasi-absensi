<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAttendance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;
        $attendances = $teacher->attendances()->orderBy('created_at', 'desc')->paginate(10);

        return view('teacher.attendance.index', compact('attendances'));
    }

    public function showScanner()
    {
        $teacher = Auth::user()->teacher;
        $settings = Setting::whereIn('key', ['school_latitude', 'school_longitude', 'school_radius'])->pluck('value', 'key');

        $hasPhoto = !empty($teacher->photo);

        return view('teacher.attendance.scanner', compact('teacher', 'settings', 'hasPhoto'));
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

        // 1. Verify Location (Server-side check as backup/validation)
        $schoolLat = Setting::where('key', 'school_latitude')->value('value');
        $schoolLng = Setting::where('key', 'school_longitude')->value('value');
        $radius = Setting::where('key', 'school_radius')->value('value');

        if ($schoolLat && $schoolLng && $radius) {
            $distance = $this->calculateDistance($request->latitude, $request->longitude, $schoolLat, $schoolLng);
            if ($distance > $radius) {
                return response()->json(['success' => false, 'message' => 'Anda berada di luar jangkauan sekolah. Jarak: ' . round($distance) . 'm'], 422);
            }
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

        $teacher->update(['photo' => $path]);

        return response()->json(['success' => true, 'message' => 'Wajah berhasil didaftarkan!']);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
