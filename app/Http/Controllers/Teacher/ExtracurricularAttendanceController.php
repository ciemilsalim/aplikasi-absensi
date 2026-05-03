<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\SchoolClass;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;

class ExtracurricularAttendanceController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;
        $extracurriculars = Extracurricular::where('teacher_id', $teacher->id)->with('students')->get();
        
        return view('teacher.extracurricular_attendance.index', compact('extracurriculars'));
    }

    public function create(Extracurricular $extracurricular)
    {
        $teacher = Auth::user()->teacher;
        if ($extracurricular->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda bukan pembina ekstrakurikuler ini.');
        }

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if (!$activeYear || !$activeSemester) {
            return back()->with('error', 'Tahun Ajaran atau Semester aktif belum ditentukan oleh Admin.');
        }

        $extracurricular->load('students.schoolClass');
        $today = Carbon::today()->toDateString();

        $existingAttendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('attendance_date', $today)
            ->get()
            ->keyBy('student_id');

        return view('teacher.extracurricular_attendance.create', compact('extracurricular', 'activeYear', 'activeSemester', 'existingAttendances', 'today'));
    }

    public function store(Request $request, Extracurricular $extracurricular)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.status' => 'required|in:hadir,sakit,izin,alpa',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if (!$activeYear || !$activeSemester) {
            return back()->with('error', 'Gagal menyimpan: Tahun Ajaran atau Semester aktif belum ditentukan oleh Admin. Silakan hubungi Admin.');
        }

        foreach ($request->attendances as $studentId => $data) {
            ExtracurricularAttendance::updateOrCreate(
                [
                    'extracurricular_id' => $extracurricular->id,
                    'student_id' => $studentId,
                    'attendance_date' => $request->attendance_date,
                ],
                [
                    'academic_year_id' => $activeYear->id,
                    'semester_id' => $activeSemester->id,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        return redirect()->route('teacher.extracurricular-attendance.index')->with('success', 'Absensi berhasil disimpan.');
    }

    public function report(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()->teacher;
        if ($extracurricular->teacher_id !== $teacher->id) {
            abort(403);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $students = $extracurricular->students()->with('schoolClass')->orderBy('name')->get();
        
        $attendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $attendanceData[$attendance->student_id][$attendance->attendance_date] = $attendance->status;
        }

        $dates = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            // Kita hanya tampilkan tanggal di mana ada aktivitas absensi agar tabel tidak terlalu lebar
            if ($attendances->contains('attendance_date', $date->toDateString())) {
                $dates[] = $date->toDateString();
            }
        }

        $settings = Setting::whereIn('key', ['app_logo', 'school_name', 'school_address', 'school_phone', 'school_email', 'school_headmaster_name', 'school_headmaster_nip'])->get();
        $schoolIdentity = [
            'logo' => $settings->firstWhere('key', 'app_logo')->value ?? null,
            'name' => $settings->firstWhere('key', 'school_name')->value ?? null,
            'address' => $settings->firstWhere('key', 'school_address')->value ?? null,
            'phone' => $settings->firstWhere('key', 'school_phone')->value ?? null,
            'email' => $settings->firstWhere('key', 'school_email')->value ?? null,
            'headmaster_name' => $settings->firstWhere('key', 'school_headmaster_name')->value ?? '..........................................',
            'headmaster_nip' => $settings->firstWhere('key', 'school_headmaster_nip')->value ?? '..........................................',
        ];

        return view('teacher.extracurricular_attendance.report', compact(
            'extracurricular',
            'students',
            'dates',
            'attendanceData',
            'startDate',
            'endDate',
            'schoolIdentity',
            'teacher'
        ));
    }
}
