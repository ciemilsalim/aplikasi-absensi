<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Traits\GpsValidationTrait;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    use GpsValidationTrait;

    public function getHomeroomStudents(Request $request)
    {
        $user    = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan guru.'], 403);
        }

        $homeroomClass = $teacher->homeroomClass;

        if (!$homeroomClass) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan Wali Kelas dari kelas manapun.'], 404);
        }

        $dateStr = $request->query('date');
        $date    = $dateStr ? Carbon::parse($dateStr)->startOfDay() : now()->startOfDay();

        $students = Student::where('school_class_id', $homeroomClass->id)
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('attendance_time', $date);
            }])
            ->get()
            ->map(function ($student) {
                $attendance = $student->attendances->first();
                return [
                    'id'            => $student->id,
                    'unique_id'     => $student->unique_id,
                    'name'          => $student->name,
                    'nis'           => $student->nis,
                    'photo_url'     => $student->photo ? asset('storage/' . $student->photo) : null,
                    'status'        => $attendance ? $attendance->status : 'belum_absen',
                    'time'          => $attendance ? $attendance->attendance_time->format('H:i:s') : null,
                    'attendance_id' => $attendance ? $attendance->id : null,
                ];
            });

        return response()->json([
            'status'     => 'success',
            'class_name' => $homeroomClass->name,
            'students'   => $students,
        ]);
    }

    public function scanQr(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
        ]);

        $gpsValidation = $this->validateGps($request->latitude, $request->longitude);
        if (!$gpsValidation['isValid']) {
            return response()->json([
                'status'  => 'error',
                'message' => $gpsValidation['message'],
            ], 422);
        }

        $user          = $request->user();
        $teacher       = $user->teacher;
        $homeroomClass = $teacher->homeroomClass;

        if (!$homeroomClass) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan Wali Kelas.'], 403);
        }

        $student = Student::where('unique_id', $request->student_unique_id)->first();

        if ($student->school_class_id !== $homeroomClass->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Siswa ini bukan bagian dari kelas Anda.',
            ], 403);
        }

        $now   = now();
        $today = $now->copy()->startOfDay();

        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('attendance_time', $today)
            ->first();

        if ($attendance && in_array($attendance->status, ['izin', 'sakit', 'alpa'])) {
            return response()->json([
                'status'       => 'on_leave',
                'message'      => 'Siswa berstatus ' . $attendance->status . ' hari ini.',
                'student_name' => $student->name,
            ], 409);
        }

        if ($attendance) {
            return response()->json([
                'status'       => 'already_clocked_in',
                'message'      => 'Siswa sudah diabsen hari ini.',
                'student_name' => $student->name,
            ], 200);
        }

        $settings        = Setting::pluck('value', 'key');
        $batasWaktuMasuk = $settings->get('jam_masuk', '07:30');
        $lateTime        = $today->copy()->setTimeFromTimeString($batasWaktuMasuk);

        $status = ($now->gt($lateTime)) ? 'terlambat' : 'tepat_waktu';

        $newAttendance = Attendance::create([
            'student_id'      => $student->id,
            'attendance_time' => $now,
            'status'          => $status,
        ]);

        return response()->json([
            'status'            => 'success',
            'message'           => 'Absensi berhasil dicatat',
            'attendance_status' => $status,
            'student_name'      => $student->name,
            'time'              => $newAttendance->attendance_time->format('H:i:s'),
        ]);
    }

    /**
     * Override/koreksi status absensi siswa oleh wali kelas.
     * Dapat mengubah "alpa" → "sakit", "belum_absen" → "izin", dll.
     */
    public function overrideAttendance(Request $request, $studentId)
    {
        $request->validate([
            'status' => 'required|in:tepat_waktu,terlambat,izin,sakit,alpa',
            'date'   => 'nullable|date',
            'notes'  => 'nullable|string|max:255',
        ]);

        $user    = $request->user();
        $teacher = $user->teacher;

        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan Wali Kelas.'], 403);
        }

        $student = Student::where('id', $studentId)
            ->where('school_class_id', $teacher->homeroomClass->id)
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan di kelas Anda.'], 404);
        }

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : now()->startOfDay();

        $attendance = Attendance::where('student_id', $studentId)
            ->whereDate('attendance_time', $date)
            ->first();

        if ($attendance) {
            $attendance->status = $request->status;
            if ($request->filled('notes')) {
                $attendance->notes = $request->notes;
            }
            $attendance->save();
        } else {
            $attendance = Attendance::create([
                'student_id'      => $studentId,
                'attendance_time' => $date->copy()->setTime(7, 0, 0),
                'status'          => $request->status,
                'notes'           => $request->notes,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Status absensi ' . $student->name . ' berhasil diubah ke "' . $request->status . '".',
            'data'    => [
                'student_name' => $student->name,
                'new_status'   => $attendance->status,
                'date'         => $date->toDateString(),
            ],
        ]);
    }
}
