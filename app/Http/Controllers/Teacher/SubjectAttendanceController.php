<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectAttendance;
use App\Models\Attendance;
use App\Models\TeachingAssignment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SubjectAttendanceController extends Controller
{
    /**
     * Menampilkan halaman pemindai QR untuk absensi mata pelajaran.
     */
    public function showScanner(Schedule $schedule)
    {
        $teacher = Auth::user()->teacher;

        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        $today = Carbon::today();
        $classId = $schedule->teachingAssignment->school_class_id;
        
        $subjectAttendancesToday = SubjectAttendance::where('schedule_id', $schedule->id)
            ->whereDate('created_at', $today)
            ->with('student')
            ->get();

        $attendedStudents = $subjectAttendancesToday->where('status', 'hadir');
        
        // Ambil siswa yang izin/sakit dari absensi harian
        $studentIdsInClass = Student::where('school_class_id', $classId)->pluck('id');
        $studentsOnLeave = Attendance::whereIn('student_id', $studentIdsInClass)
            ->whereDate('attendance_time', $today)
            ->whereIn('status', ['sakit', 'izin'])
            ->with('student')
            ->get();

        $studentIdsWithRecord = $subjectAttendancesToday->pluck('student_id')->merge($studentsOnLeave->pluck('student_id'))->unique();

        $studentsWithoutNotice = Student::where('school_class_id', $classId)
            ->whereNotIn('id', $studentIdsWithRecord)
            ->orderBy('name', 'asc')
            ->get();

        return view('teacher.subject_attendance_scanner', compact('schedule', 'attendedStudents', 'studentsOnLeave', 'studentsWithoutNotice'));
    }

    /**
     * Menyimpan data absensi mata pelajaran dari hasil pemindaian.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
            'schedule_id' => 'required|integer|exists:schedules,id',
        ]);

        $teacher = Auth::user()->teacher;
        $schedule = Schedule::find($request->schedule_id);
        $student = Student::where('unique_id', $request->student_unique_id)->first();
        $today = Carbon::today();

        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }
        
        if ($student->school_class_id !== $schedule->teachingAssignment->school_class_id) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak terdaftar di kelas ini.'], 422);
        }

        $existingAttendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json(['success' => false, 'message' => 'Siswa sudah diabsen sebelumnya.'], 409);
        }

        $attendance = SubjectAttendance::create([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'hadir',
        ]);

        // --- PERUBAHAN DI SINI ---
        return response()->json([
            'success' => true,
            'message' => 'Kehadiran ' . $student->name . ' berhasil dicatat.',
            'student' => [
                'id' => $student->id, // ID siswa ditambahkan ke respons
                'name' => $student->name, 
                'time' => $attendance->created_at->format('H:i:s')
            ]
        ]);
    }

    /**
     * Menampilkan halaman riwayat absensi mata pelajaran.
     */
    public function showHistory(Request $request)
    {
        $teacher = Auth::user()->teacher;
        
        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $attendances = SubjectAttendance::with(['student', 'schedule.teachingAssignment.subject', 'schedule.teachingAssignment.schoolClass'])
            ->where('teacher_id', $teacher->id)
            ->whereDate('created_at', $selectedDate)
            ->get()
            ->groupBy('schedule_id');

        return view('teacher.subject_attendance_history', compact('attendances', 'selectedDate'));
    }

    /**
     * Menandai status siswa secara manual oleh guru mapel.
     */
    public function markManualAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'schedule_id' => 'required|exists:schedules,id',
            'status' => 'required|in:sakit,izin,alpa,bolos',
        ]);

        $teacher = Auth::user()->teacher;
        $student = Student::find($request->student_id);
        $schedule = Schedule::find($request->schedule_id);
        $today = Carbon::today();

        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $attendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($attendance) {
            $attendance->update([
                'status' => $request->status,
                'teacher_id' => $teacher->id,
            ]);
        } else {
            SubjectAttendance::create([
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => $request->status,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status ' . $student->name . ' berhasil diubah menjadi ' . $request->status,
        ]);
    }

    /**
     * Menampilkan halaman formulir untuk filter rekap absensi.
     */
    public function showReportForm()
    {
        $teacher = Auth::user()->teacher;

        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass.name', 'schoolClass.id')->unique();
        $subjects = $assignments->pluck('subject.name', 'subject.id')->unique();

        return view('teacher.report_form', compact('classes', 'subjects'));
    }

    /**
     * Menghasilkan dan menampilkan halaman cetak rekap absensi.
     */
    public function printReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'school_class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $teacher = Auth::user()->teacher;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $schoolClassId = $request->school_class_id;
        $subjectId = $request->subject_id;

        $students = Student::where('school_class_id', $schoolClassId)->orderBy('name')->get();

        $attendances = SubjectAttendance::with('student')
            ->where('teacher_id', $teacher->id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereHas('schedule.teachingAssignment', function ($query) use ($schoolClassId, $subjectId) {
                $query->where('school_class_id', $schoolClassId)
                      ->where('subject_id', $subjectId);
            })
            ->get();

        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->created_at)->format('Y-m-d');
            $attendanceData[$attendance->student_id][$date] = $attendance->status;
        }

        $classInfo = \App\Models\SchoolClass::find($schoolClassId);
        $subjectInfo = \App\Models\Subject::find($subjectId);

        return view('teacher.report_print', compact(
            'students', 
            'dates', 
            'attendanceData', 
            'classInfo', 
            'subjectInfo', 
            'startDate', 
            'endDate'
        ));
    }
}
