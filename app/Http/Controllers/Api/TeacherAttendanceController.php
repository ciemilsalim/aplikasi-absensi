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
                    'in_time'       => $attendance ? $attendance->attendance_time->format('H:i:s') : null,
                    'out_time'      => ($attendance && $attendance->checkout_time) ? $attendance->checkout_time->format('H:i:s') : null,
                    'is_checked_out'=> ($attendance && $attendance->checkout_time) ? true : false,
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
            'type'              => 'nullable|string|in:in,out',
        ]);

        $type = $request->input('type', 'in');

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

        // 1. Cek Akhir Pekan
        if ($today->isWeekend()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Absen tidak dapat dilakukan pada akhir pekan.',
            ], 422);
        }

        // 2. Cek Hari Libur
        $holidays = \App\Models\Calendar::getHolidaysInRange($today, $today);
        if (\App\Models\Calendar::isDateInHolidays($today, $holidays)) {
            $holiday = $holidays->first(function($h) use ($today) {
                $start = $h->start_date->copy()->startOfDay();
                $end = $h->end_date ? $h->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
                return $today->between($start, $end);
            });
            $title = $holiday ? $holiday->title : 'Hari Libur';
            return response()->json([
                'status'  => 'error',
                'message' => "Absen dibatalkan: $title (Hari Libur).",
            ], 422);
        }

        // 3. Cek Belajar Mandiri
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($today, $today);
        if (\App\Models\Calendar::isDateInSelfStudy($today, $selfStudyDays)) {
            $selfStudy = $selfStudyDays->first(function($h) use ($today) {
                $start = $h->start_date->copy()->startOfDay();
                $end = $h->end_date ? $h->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
                return $today->between($start, $end);
            });
            $title = $selfStudy ? $selfStudy->title : 'Belajar Mandiri';
            return response()->json([
                'status'  => 'error',
                'message' => "Absen dibatalkan: $title (Belajar Mandiri).",
            ], 422);
        }

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

        if ($type === 'in') {
            if ($attendance) {
                return response()->json([
                    'status'       => 'already_clocked_in',
                    'message'      => 'Siswa sudah absen masuk hari ini.',
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
                'message'           => 'Absensi datang berhasil dicatat',
                'attendance_status' => $status,
                'student_name'      => $student->name,
                'time'              => $newAttendance->attendance_time->format('H:i:s'),
            ]);
        } else {
            // Type == 'out'
            if (!$attendance) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Siswa belum absen datang. Silakan absen datang terlebih dahulu.',
                ], 422);
            }

            if ($attendance->checkout_time) {
                return response()->json([
                    'status'       => 'already_clocked_out',
                    'message'      => 'Siswa sudah absen pulang hari ini.',
                    'student_name' => $student->name,
                ], 200);
            }

            $attendance->update([
                'checkout_time' => $now,
            ]);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Absensi pulang berhasil dicatat',
                'student_name' => $student->name,
                'time'         => $now->format('H:i:s'),
            ]);
        }
    }

    /**
     * Override/koreksi status absensi siswa oleh wali kelas.
     * Dapat mengubah "alpa" → "sakit", "belum_absen" → "izin", dll.
     */
    public function overrideAttendance(Request $request, $studentId)
    {
        $request->validate([
            'status' => 'required|in:tepat_waktu,terlambat,izin,sakit,alpa,pulang',
            'type'   => 'nullable|in:in,out',
            'date'   => 'nullable|date',
            'notes'  => 'nullable|string|max:255',
        ]);

        $type = $request->input('type', 'in');
        if ($request->status === 'pulang') {
            $type = 'out';
        }

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

        // 1. Cek Akhir Pekan
        if ($date->isWeekend()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Absen tidak dapat dikoreksi/diisi pada akhir pekan.',
            ], 422);
        }

        // 2. Cek Hari Libur
        $holidays = \App\Models\Calendar::getHolidaysInRange($date, $date);
        if (\App\Models\Calendar::isDateInHolidays($date, $holidays)) {
            $holiday = $holidays->first(function($h) use ($date) {
                $start = $h->start_date->copy()->startOfDay();
                $end = $h->end_date ? $h->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
                return $date->between($start, $end);
            });
            $title = $holiday ? $holiday->title : 'Hari Libur';
            return response()->json([
                'status'  => 'error',
                'message' => "Tidak dapat mengubah absen: $title (Hari Libur).",
            ], 422);
        }

        // 3. Cek Belajar Mandiri
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($date, $date);
        if (\App\Models\Calendar::isDateInSelfStudy($date, $selfStudyDays)) {
            $selfStudy = $selfStudyDays->first(function($h) use ($date) {
                $start = $h->start_date->copy()->startOfDay();
                $end = $h->end_date ? $h->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
                return $date->between($start, $end);
            });
            $title = $selfStudy ? $selfStudy->title : 'Belajar Mandiri';
            return response()->json([
                'status'  => 'error',
                'message' => "Tidak dapat mengubah absen: $title (Belajar Mandiri).",
            ], 422);
        }

        $attendance = Attendance::where('student_id', $studentId)
            ->whereDate('attendance_time', $date)
            ->first();

        if ($type === 'in') {
            $status = $request->status === 'pulang' ? 'tepat_waktu' : $request->status;

            if ($attendance) {
                $attendance->status = $status;
                if ($request->filled('notes')) {
                    $attendance->notes = $request->notes;
                }
                
                // Jika status diubah jadi tidak hadir, hapus jam pulang
                if (in_array($status, ['izin', 'sakit', 'alpa'])) {
                    $attendance->checkout_time = null;
                }
                
                $attendance->save();
            } else {
                $attendance = Attendance::create([
                    'student_id'      => $studentId,
                    'attendance_time' => $date->copy()->setTime(now()->hour, now()->minute, now()->second),
                    'status'          => $status,
                    'notes'           => $request->notes,
                ]);
            }
        } else {
            // type == 'out'
            if ($attendance) {
                $attendance->checkout_time = $request->status === 'alpa' ? null : now();
                if ($request->filled('notes')) {
                    $attendance->notes = $request->notes;
                }
                $attendance->save();
            } else {
                // Buat record baru jika belum ada (meskipun idealnya in dulu)
                // Tapi untuk override wali kelas kita izinkan
                $attendance = Attendance::create([
                    'student_id'      => $studentId,
                    'attendance_time' => $date->copy()->setTime(7, 0, 0),
                    'checkout_time'   => now(),
                    'status'          => 'tepat_waktu',
                    'notes'           => $request->notes,
                ]);
            }
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
