<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\StudentPermit;
use App\Models\Schedule;
use App\Models\SubjectAttendance;
use App\Models\TeachingAssignment;
use App\Models\TeacherNote;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Cocurricular;
use App\Models\Announcement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exports\AttendanceReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    private function checkEffectiveDaysSet()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        $effectiveDays = Setting::where('key', 'effective_days_' . $currentYear . '_' . $currentMonth)->value('value');
        if ($effectiveDays === null) {
            $effectiveDays = Setting::where('key', 'effective_days_' . $currentMonth)->value('value');
        }
        return !empty($effectiveDays) && $effectiveDays > 0;
    }

    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            abort(403, 'Akses ditolak. Anda bukan seorang guru.');
        }

        $viewData = ['teacher' => $teacher];

        $isHomeroomTeacher = $teacher->homeroomClass()->exists();
        $isSubjectTeacher = $teacher->teachingAssignments()->exists();
        $isExtracurricularCoach = $teacher->coachingExtracurriculars()->exists();
        $isCocurricularFacilitator = $teacher->cocurriculars()->exists() 
            || Schedule::where('teacher_id', $teacher->id)->where('schedule_type', 'cocurricular')->exists();

        if (!$isHomeroomTeacher && !$isSubjectTeacher && !$isExtracurricularCoach && !$isCocurricularFacilitator) {
            return view('teacher.dashboard-no-role', $viewData);
        }

        // Tentukan view default berdasarkan prioritas
        if ($isHomeroomTeacher) {
            $defaultView = 'wali_kelas';
        } elseif ($isSubjectTeacher) {
            $defaultView = 'guru_mapel';
        } elseif ($isCocurricularFacilitator) {
            $defaultView = 'fasilitator_kokurikuler';
        } else {
            $defaultView = 'pembina_ekskul';
        }
        $currentView = $request->input('view', $defaultView);

        $viewData['isHomeroomTeacher'] = $isHomeroomTeacher;
        $viewData['isSubjectTeacher'] = $isSubjectTeacher;
        $viewData['isExtracurricularCoach'] = $isExtracurricularCoach;
        $viewData['isCocurricularFacilitator'] = $isCocurricularFacilitator;
        $viewData['currentView'] = $currentView;
        $viewData['isEffectiveDaysSet'] = $this->checkEffectiveDaysSet();

        if ($currentView === 'wali_kelas' && $isHomeroomTeacher) {
            $viewData = array_merge($viewData, $this->getHomeroomData($teacher));
        }

        if ($currentView === 'guru_mapel' && $isSubjectTeacher) {
            $viewData = array_merge($viewData, $this->getSubjectTeacherData($teacher));
        }

        if ($currentView === 'fasilitator_kokurikuler' && $isCocurricularFacilitator) {
            $viewData = array_merge($viewData, $this->getCocurricularFacilitatorData($teacher));
        }

        if ($currentView === 'pembina_ekskul' && $isExtracurricularCoach) {
            $viewData = array_merge($viewData, $this->getExtracurricularCoachData($teacher));
        }

        if (!isset($viewData['schedulesToday'])) {
            $viewData['schedulesToday'] = collect();
        }
        if (!isset($viewData['allSchedules'])) {
            $viewData['allSchedules'] = collect();
        }
        if (!isset($viewData['chartLabels'])) {
            $viewData['chartLabels'] = [];
        }
        if (!isset($viewData['classPerformanceData'])) {
            $viewData['classPerformanceData'] = [];
        }
        if (!isset($viewData['teacherNote'])) {
            $viewData['teacherNote'] = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);
        }

        // Ambil pengumuman terbaru
        $viewData['announcements'] = Announcement::whereNotNull('published_at')
                                     ->where('published_at', '<=', now())
                                     ->latest('published_at')
                                     ->take(3)
                                     ->get();

        return view('teacher.dashboard', $viewData);
    }

    private function getHomeroomData($teacher)
    {
        $class = $teacher->homeroomClass;
        $today = Carbon::today();
        $thirtyDaysAgo = now()->subDays(30);

        $studentsInClass = $class ? Student::where('school_class_id', $class->id)->orderBy('name')->get() : collect();
        $studentIds = $studentsInClass->pluck('id');

        $attendancesToday = Attendance::whereIn('student_id', $studentIds)
            ->whereDate('attendance_time', $today)
            ->get()
            ->keyBy('student_id');

        $totalStudents = $studentsInClass->count();

        $studentsOnPermit = StudentPermit::with('student')
            ->whereIn('student_id', $studentIds)
            ->whereDate('time_out', $today)
            ->whereNull('time_in')
            ->get();

        $studentsNotCheckedOut = Attendance::with('student')
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_time', $today)
            ->whereNotNull('attendance_time')
            ->whereNull('checkout_time')
            ->whereNotIn('status', ['izin', 'sakit', 'alpa', 'izin_keluar'])
            ->get();

        $presentStudentIds = $attendancesToday->keys();
        $absentStudents = $studentsInClass->whereNotIn('id', $presentStudentIds)->values();
        $totalBelumAbsen = $absentStudents->count();

        // Rekap status kehadiran hari ini
        $onTimeCount = $attendancesToday->where('status', 'tepat_waktu')->count();
        $lateCount = $attendancesToday->where('status', 'terlambat')->count();
        $sickCount = $attendancesToday->where('status', 'sakit')->count();
        $permitCount = $attendancesToday->whereIn('status', ['izin', 'izin_keluar'])->count();
        $alphaCount = $attendancesToday->where('status', 'alpa')->count();
        $noRecordCount = $totalBelumAbsen;

        // Check if today is an effective school day
        $isWeekend = $today->isWeekend();
        $holidaysToday = \App\Models\Calendar::getHolidaysInRange($today, $today);
        $isHoliday = \App\Models\Calendar::isDateInHolidays($today, $holidaysToday);
        $isEffectiveSchoolDay = !$isWeekend && !$isHoliday;

        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $period = CarbonPeriod::create($startDate, $endDate);

        $weeklyAttendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->get()
            ->groupBy(fn($date) => Carbon::parse($date->attendance_time)->format('Y-m-d'));

        $chartLabels = [];
        $chartData = [];
        foreach ($period as $date) {
            $chartLabels[] = $date->translatedFormat('D, d M');
            $dateString = $date->format('Y-m-d');
            $attendedCount = $weeklyAttendances->has($dateString) ? $weeklyAttendances[$dateString]->count() : 0;
            $percentage = ($totalStudents > 0) ? round(($attendedCount / $totalStudents) * 100) : 0;
            $chartData[] = $percentage;
        }

        // Siswa yang perlu perhatian khusus (dihitung aman di Collection tanpa memicu sql_mode ONLY_FULL_GROUP_BY error)
        $studentsForAttention = Student::whereIn('id', $studentIds)
            ->withCount([
                'attendances as late_count' => fn($query) => $query->where('status', 'terlambat')->where('attendance_time', '>=', $thirtyDaysAgo),
                'attendances as alpha_count' => fn($query) => $query->where('status', 'alpa')->where('attendance_time', '>=', $thirtyDaysAgo)
            ])
            ->get()
            ->filter(fn($student) => ($student->late_count > 0 || $student->alpha_count > 0))
            ->sortByDesc(fn($student) => $student->late_count + $student->alpha_count)
            ->take(5)
            ->values();

        $dailyPresencePercentage = 0;
        if ($totalStudents > 0) {
            $presentTodayCount = $attendancesToday->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
            $dailyPresencePercentage = round(($presentTodayCount / $totalStudents) * 100);
        }

        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'class' => $class,
            'homeroomClass' => $class,
            'studentsInClass' => $studentsInClass,
            'attendancesToday' => $attendancesToday,
            'totalStudents' => $totalStudents,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'studentsForAttention' => $studentsForAttention,
            'studentsForAttentionWali' => $studentsForAttention,
            'dailyPresencePercentage' => $dailyPresencePercentage,
            'studentsOnPermit' => $studentsOnPermit,
            'studentsNotCheckedOut' => $studentsNotCheckedOut,
            'absentStudents' => $absentStudents,
            'totalBelumAbsen' => $totalBelumAbsen,
            'isEffectiveSchoolDay' => $isEffectiveSchoolDay,
            'onTimeCount' => $onTimeCount,
            'lateCount' => $lateCount,
            'sickCount' => $sickCount,
            'permitCount' => $permitCount,
            'alphaCount' => $alphaCount,
            'noRecordCount' => $noRecordCount,
            'teacherNote' => $teacherNote,
        ];
    }

    private function getSubjectTeacherData($teacher)
    {
        $now = now();
        $dayOfWeekNumber = $now->dayOfWeek;

        $schedulesToday = Schedule::with([
            'teachingAssignment.schoolClass',
            'teachingAssignment.subject'
        ])
            ->where('schedule_type', 'regular')
            ->where('day_of_week', $dayOfWeekNumber)
            ->whereHas('teachingAssignment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderBy('start_time', 'asc')
            ->get();

        $currentMonth = $now->month;
        if ($currentMonth >= 7 && $currentMonth <= 12) {
            $semesterStart = $now->copy()->setMonth(7)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(12)->endOfMonth();
        } else {
            $semesterStart = $now->copy()->setMonth(1)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(6)->endOfMonth();
        }

        $studentsForAttention = SubjectAttendance::where('teacher_id', $teacher->id)
            ->whereIn('status', ['alpa', 'bolos'])
            ->whereBetween('created_at', [$semesterStart, $semesterEnd])
            ->with('student.schoolClass')
            ->select(
                'student_id',
                DB::raw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as alpa_count'),
                DB::raw('SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) as bolos_count')
            )
            ->groupBy('student_id')
            ->havingRaw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) + SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) > 0')
            ->orderByRaw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) + SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) DESC')
            ->take(5)
            ->get();

        $lastAttendanceSummary = null;
        $lastAttendanceRecord = SubjectAttendance::where('teacher_id', $teacher->id)
            ->whereHas('schedule', function ($q) {
                $q->where('schedule_type', 'regular');
            })
            ->latest()
            ->first();

        if ($lastAttendanceRecord) {
            $attendances = SubjectAttendance::where('schedule_id', $lastAttendanceRecord->schedule_id)
                ->whereDate('created_at', $lastAttendanceRecord->created_at->toDateString())
                ->get();

            $summary = $attendances->countBy('status');
            $lastAttendanceSummary = [
                'schedule' => $lastAttendanceRecord->schedule,
                'hadir' => $summary->get('hadir', 0),
                'sakit' => $summary->get('sakit', 0),
                'izin' => $summary->get('izin', 0),
                'alpa' => $summary->get('alpa', 0),
                'bolos' => $summary->get('bolos', 0),
            ];
        }

        $classPerformanceData = [];
        $thirtyDaysAgo = now()->subDays(30);
        $assignments = TeachingAssignment::where('teacher_id', $teacher->id)
            ->with('schoolClass', 'subject')
            ->get();

        foreach ($assignments as $assignment) {
            $scheduleIds = Schedule::where('teaching_assignment_id', $assignment->id)->pluck('id');
            if ($scheduleIds->isEmpty())
                continue;
            $totalSessions = SubjectAttendance::whereIn('schedule_id', $scheduleIds)->where('created_at', '>=', $thirtyDaysAgo)->distinct(DB::raw('DATE(created_at)'))->count();
            $totalHadir = SubjectAttendance::whereIn('schedule_id', $scheduleIds)->where('status', 'hadir')->where('created_at', '>=', $thirtyDaysAgo)->count();
            $totalStudentsInClass = Student::where('school_class_id', $assignment->school_class_id)->count();
            $potentialAttendance = $totalStudentsInClass * $totalSessions;
            $percentage = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100) : 0;
            $classPerformanceData[] = [
                'label' => ($assignment->schoolClass?->name ?? 'Kelas') . ' - ' . ($assignment->subject?->name ?? 'Mapel'),
                'percentage' => $percentage,
            ];
        }

        $allSchedules = Schedule::with([
            'teachingAssignment.schoolClass',
            'teachingAssignment.subject'
        ])
            ->where('schedule_type', 'regular')
            ->whereHas('teachingAssignment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'schedulesToday' => $schedulesToday,
            'allSchedules' => $allSchedules,
            'studentsForAttentionMapel' => $studentsForAttention,
            'lastAttendanceSummary' => $lastAttendanceSummary,
            'classPerformanceData' => $classPerformanceData,
            'teacherNote' => $teacherNote,
        ];
    }

    /**
     * Mengambil data dasbor untuk Fasilitator Kokurikuler.
     */
    private function getCocurricularFacilitatorData($teacher)
    {
        $now = now();
        $dayOfWeekNumber = $now->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat

        $cocurricularIds = $teacher->cocurriculars()->pluck('cocurriculars.id');

        // Jadwal hari ini
        $schedulesToday = Schedule::with([
            'cocurricular',
            'schoolClass',
            'teacher'
        ])
            ->where('schedule_type', 'cocurricular')
            ->where('day_of_week', $dayOfWeekNumber)
            ->where(function ($query) use ($teacher, $cocurricularIds) {
                $query->where('teacher_id', $teacher->id)
                      ->orWhereIn('cocurricular_id', $cocurricularIds);
            })
            ->orderBy('start_time', 'asc')
            ->get();

        // Semua jadwal mingguan
        $allSchedules = Schedule::with([
            'cocurricular',
            'schoolClass',
            'teacher'
        ])
            ->where('schedule_type', 'cocurricular')
            ->where(function ($query) use ($teacher, $cocurricularIds) {
                $query->where('teacher_id', $teacher->id)
                      ->orWhereIn('cocurricular_id', $cocurricularIds);
            })
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $cocurricularScheduleIds = $allSchedules->pluck('id');

        $currentMonth = $now->month;
        if ($currentMonth >= 7 && $currentMonth <= 12) {
            $semesterStart = $now->copy()->setMonth(7)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(12)->endOfMonth();
        } else {
            $semesterStart = $now->copy()->setMonth(1)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(6)->endOfMonth();
        }

        // Siswa perlu perhatian khusus di kokurikuler
        $studentsForAttention = SubjectAttendance::whereIn('schedule_id', $cocurricularScheduleIds)
            ->whereIn('status', ['alpa', 'bolos'])
            ->whereBetween('created_at', [$semesterStart, $semesterEnd])
            ->with('student.schoolClass')
            ->select(
                'student_id',
                DB::raw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as alpa_count'),
                DB::raw('SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) as bolos_count')
            )
            ->groupBy('student_id')
            ->havingRaw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) + SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) > 0')
            ->orderByRaw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) + SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) DESC')
            ->take(5)
            ->get();

        // Ringkasan presensi terakhir sesi kokurikuler
        $lastAttendanceSummary = null;
        $lastAttendanceRecord = SubjectAttendance::whereIn('schedule_id', $cocurricularScheduleIds)
            ->latest()
            ->first();

        if ($lastAttendanceRecord) {
            $attendances = SubjectAttendance::where('schedule_id', $lastAttendanceRecord->schedule_id)
                ->whereDate('created_at', $lastAttendanceRecord->created_at->toDateString())
                ->get();

            $summary = $attendances->countBy('status');
            $lastAttendanceSummary = [
                'schedule' => $lastAttendanceRecord->schedule,
                'hadir' => $summary->get('hadir', 0),
                'sakit' => $summary->get('sakit', 0),
                'izin' => $summary->get('izin', 0),
                'alpa' => $summary->get('alpa', 0),
                'bolos' => $summary->get('bolos', 0),
            ];
        }

        // Performa Kehadiran Kelas Kokurikuler 30 Hari Terakhir
        $classPerformanceData = [];
        $thirtyDaysAgo = now()->subDays(30);

        $distinctPairs = $allSchedules->unique(function ($item) {
            return $item->cocurricular_id . '-' . $item->school_class_id;
        });

        foreach ($distinctPairs as $item) {
            $pairScheduleIds = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $item->cocurricular_id)
                ->where('school_class_id', $item->school_class_id)
                ->pluck('id');

            if ($pairScheduleIds->isEmpty()) continue;

            $totalSessions = SubjectAttendance::whereIn('schedule_id', $pairScheduleIds)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->selectRaw('DATE(created_at) as session_date')
                ->distinct()
                ->get()
                ->count();

            $totalHadir = SubjectAttendance::whereIn('schedule_id', $pairScheduleIds)
                ->where('status', 'hadir')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count();

            $totalStudentsInClass = Student::where('school_class_id', $item->school_class_id)->count();
            $potentialAttendance = $totalStudentsInClass * $totalSessions;
            $percentage = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100) : 0;

            $classPerformanceData[] = [
                'label' => ($item->schoolClass?->name ?? 'Kelas') . ' - ' . \Illuminate\Support\Str::limit($item->cocurricular?->title ?? 'Kokurikuler', 25),
                'percentage' => $percentage,
            ];
        }

        $myProjects = $teacher->cocurriculars()->with(['level', 'teachers'])->get();
        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'schedulesTodayKokurikuler' => $schedulesToday,
            'allSchedulesKokurikuler' => $allSchedules,
            'studentsForAttentionKokurikuler' => $studentsForAttention,
            'lastAttendanceSummaryKokurikuler' => $lastAttendanceSummary,
            'classPerformanceDataKokurikuler' => $classPerformanceData,
            'myCocurricularProjects' => $myProjects,
            'teacherNote' => $teacherNote,
        ];
    }

    private function getExtracurricularCoachData($teacher)
    {
        $today = Carbon::today();
        $extracurriculars = $teacher->coachingExtracurriculars()
            ->with(['teachers', 'students.schoolClass'])
            ->withCount('students')
            ->get();

        $ekskulIds = $extracurriculars->pluck('id');

        $todayAttendanceStats = [];
        foreach ($extracurriculars as $ekskul) {
            $stats = ExtracurricularAttendance::where('extracurricular_id', $ekskul->id)
                ->whereDate('attendance_date', $today)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $todayAttendanceStats[$ekskul->id] = [
                'hadir' => $stats->get('hadir', 0),
                'sakit' => $stats->get('sakit', 0),
                'izin' => $stats->get('izin', 0),
                'alpa' => $stats->get('alpa', 0),
                'total_recorded' => $stats->sum(),
            ];
        }

        $now = now();
        $currentMonth = $now->month;
        if ($currentMonth >= 7 && $currentMonth <= 12) {
            $semesterStart = $now->copy()->setMonth(7)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(12)->endOfMonth();
        } else {
            $semesterStart = $now->copy()->setMonth(1)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(6)->endOfMonth();
        }

        // Siswa ekskul yang sering alpa
        $studentsForAttentionEkskul = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->where('status', 'alpa')
            ->whereBetween('attendance_date', [$semesterStart->toDateString(), $semesterEnd->toDateString()])
            ->with(['student.schoolClass', 'extracurricular'])
            ->select('student_id', 'extracurricular_id', DB::raw('count(*) as alpa_count'))
            ->groupBy('student_id', 'extracurricular_id')
            ->havingRaw('count(*) > 0')
            ->orderByDesc('alpa_count')
            ->take(5)
            ->get();

        // Ringkasan presensi sesi latihan terakhir
        $lastAttendanceSummaryEkskul = null;
        $lastRecord = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->latest('attendance_date')
            ->first();

        if ($lastRecord) {
            $attendances = ExtracurricularAttendance::where('extracurricular_id', $lastRecord->extracurricular_id)
                ->where('attendance_date', $lastRecord->attendance_date)
                ->get();

            $summary = $attendances->countBy('status');
            $lastAttendanceSummaryEkskul = [
                'extracurricular' => $lastRecord->extracurricular,
                'date' => Carbon::parse($lastRecord->attendance_date),
                'hadir' => $summary->get('hadir', 0),
                'sakit' => $summary->get('sakit', 0),
                'izin' => $summary->get('izin', 0),
                'alpa' => $summary->get('alpa', 0),
                'total' => $attendances->count(),
            ];
        }

        // Performa Kehadiran Ekskul 30 Hari Terakhir
        $thirtyDaysAgo = now()->subDays(30);
        $classPerformanceDataEkskul = [];
        foreach ($extracurriculars as $ekskul) {
            $totalSessions = ExtracurricularAttendance::where('extracurricular_id', $ekskul->id)
                ->where('attendance_date', '>=', $thirtyDaysAgo->toDateString())
                ->distinct('attendance_date')
                ->count('attendance_date');

            $totalHadir = ExtracurricularAttendance::where('extracurricular_id', $ekskul->id)
                ->where('status', 'hadir')
                ->where('attendance_date', '>=', $thirtyDaysAgo->toDateString())
                ->count();

            $totalMembers = $ekskul->students_count;
            $potentialAttendance = $totalMembers * $totalSessions;
            $percentage = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100) : 0;

            $classPerformanceDataEkskul[] = [
                'label' => $ekskul->name,
                'percentage' => $percentage,
                'total_sessions' => $totalSessions,
            ];
        }

        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'coachedExtracurriculars' => $extracurriculars,
            'todayExtracurricularStats' => $todayAttendanceStats,
            'studentsForAttentionEkskul' => $studentsForAttentionEkskul,
            'lastAttendanceSummaryEkskul' => $lastAttendanceSummaryEkskul,
            'classPerformanceDataEkskul' => $classPerformanceDataEkskul,
            'teacherNote' => $teacherNote,
        ];
    }

    public function updateAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|string|in:tepat_waktu,terlambat,sakit,izin,alpa,hapus',
        ]);

        $teacher = Auth::user()?->teacher;
        $student = Student::find($request->student_id);

        if (!$teacher || !$student || !$teacher->homeroomClass || $teacher->homeroomClass->id !== $student->school_class_id) {
            return back()->with('error', 'Anda tidak berwenang mengubah absensi siswa ini.');
        }

        $status = $request->input('status');
        $date = Carbon::parse($request->input('date'))->startOfDay();

        $attendance = Attendance::where('student_id', $request->student_id)
            ->whereDate('attendance_time', $date)
            ->first();

        if ($status === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return back()->with('success', 'Riwayat absensi berhasil dihapus.');
            }
            return back()->with('success', 'Tidak ada perubahan dilakukan.');
        }

        if ($attendance) {
            $attendance->status = $status;
            if (is_null($attendance->attendance_time)) {
                $attendance->attendance_time = $date;
            }
            $attendance->save();
        } else {
            $attendance = Attendance::create([
                'student_id' => $request->student_id,
                'status' => $status,
                'attendance_time' => $date,
            ]);
        }

        if ($status !== 'alpa') {
            foreach ($student->parents as $parent) {
                if ($parent->user_id) {
                    \App\Models\Notification::where('user_id', $parent->user_id)
                        ->where('is_read', false)
                        ->where('title', 'like', '%' . $student->name . '%')
                        ->update(['is_read' => true]);
                }
            }
        }

        return back()->with('success', 'Riwayat absensi berhasil diperbarui.');
    }

    public function showAttendanceHistory(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $class = $teacher->homeroomClass;
        $students = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $selectedDate = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();

        $allAttendancesInMonth = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $attendances = $allAttendancesInMonth
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $period = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !\App\Models\Calendar::isDateInHolidays($date, $holidays);
        });

        $attendanceSummary = [];
        $totalClassHadir = 0;
        $totalClassSakit = 0;
        $totalClassIzin = 0;
        $totalClassAlpa = 0;

        foreach ($students as $student) {
            $studentAttendances = $allAttendancesInMonth->where('student_id', $student->id);
            $hadirCount = 0; $sakitCount = 0; $izinCount = 0; $alpaCount = 0;
            
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $attendanceRecord = $studentAttendances->firstWhere(function($item) use ($dateString) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                });
                
                if ($attendanceRecord) {
                    if (in_array($attendanceRecord->status, ['tepat_waktu', 'terlambat'])) $hadirCount++;
                    elseif ($attendanceRecord->status === 'sakit') $sakitCount++;
                    elseif (in_array($attendanceRecord->status, ['izin', 'izin_keluar'])) $izinCount++;
                    elseif ($attendanceRecord->status === 'alpa') $alpaCount++;
                } else {
                    if ($date->isPast() && !$date->isToday()) {
                        $alpaCount++;
                    }
                }
            }
            $attendanceSummary[$student->id] = [
                'hadir' => $hadirCount,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
            ];

            $totalClassHadir += $hadirCount;
            $totalClassSakit += $sakitCount;
            $totalClassIzin += $izinCount;
            $totalClassAlpa += $alpaCount;
        }

        $totalEffectiveWorkdays = $period->count();
        $totalPossibleAttendance = $students->count() * $totalEffectiveWorkdays;
        $classAvgPercent = $totalPossibleAttendance > 0 ? round(($totalClassHadir / $totalPossibleAttendance) * 100, 1) : 0;

        return view('teacher.attendance-history', compact(
            'class', 'students', 'attendances', 'period', 'selectedDate', 
            'attendanceSummary', 'holidays', 'selfStudyDays',
            'classAvgPercent', 'totalEffectiveWorkdays',
            'totalClassSakit', 'totalClassIzin', 'totalClassAlpa'
        ));
    }

    public function printAttendance(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $class = $teacher->homeroomClass;
        $students = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'paper_size' => 'nullable|string|in:a4,folio,f4',
        ]);

        $selectedDate = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();
        $paperSize = strtolower($request->input('paper_size', 'a4'));

        $allAttendancesInMonth = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $attendances = $allAttendancesInMonth
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $period = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !\App\Models\Calendar::isDateInHolidays($date, $holidays);
        });

        $effectiveDaysCount = $period->count();
        $totalStudents = $students->count();

        $attendanceSummary = [];
        $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0;

        foreach ($students as $student) {
            $studentAttendances = $allAttendancesInMonth->where('student_id', $student->id);
            $hadirCount = 0; $sakitCount = 0; $izinCount = 0; $alpaCount = 0;
            
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($date, $selfStudyDays);
                
                if ($isSelfStudy) {
                    $hadirCount++;
                } else {
                    $attendanceRecord = $studentAttendances->firstWhere(function($item) use ($dateString) {
                        return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                    });
                    
                    if ($attendanceRecord) {
                        if (in_array($attendanceRecord->status, ['tepat_waktu', 'terlambat'])) $hadirCount++;
                        elseif ($attendanceRecord->status === 'sakit') $sakitCount++;
                        elseif (in_array($attendanceRecord->status, ['izin', 'izin_keluar'])) $izinCount++;
                        elseif ($attendanceRecord->status === 'alpa') $alpaCount++;
                    } else {
                        if ($date->isPast() && !$date->isToday()) {
                            $alpaCount++;
                        }
                    }
                }
            }

            $totalAbsen = $sakitCount + $izinCount + $alpaCount;
            $jml = $totalAbsen;
            if ($effectiveDaysCount > 0) {
                $persen = round((($effectiveDaysCount - $totalAbsen) / $effectiveDaysCount) * 100, 0);
                $persen = max(0, min(100, $persen));
                $persenStr = $persen . '%';
            } else {
                $persen = 0;
                $persenStr = '0%';
            }

            $attendanceSummary[$student->id] = [
                'hadir' => $hadirCount,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
                'jml' => $jml,
                'persen' => $persen,
                'persen_str' => $persenStr,
            ];

            $totalHadir += $hadirCount;
            $totalSakit += $sakitCount;
            $totalIzin += $izinCount;
            $totalAlpa += $alpaCount;
        }

        $totalPossibleAttendance = $totalStudents * $effectiveDaysCount;
        $classAverageAttendance = $totalPossibleAttendance > 0 ? round(($totalHadir / $totalPossibleAttendance) * 100, 1) : 0;
        $perfectAttendanceCount = collect($attendanceSummary)->filter(fn($s) => $s['persen'] >= 100)->count();
        $needsAttentionCount = collect($attendanceSummary)->filter(fn($s) => $s['persen'] < 85)->count();

        $settings = Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            try {
                $logoData = Storage::disk('public')->get($logoPath);
                $logoBase64 = 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            } catch (\Exception $e) {
                $logoBase64 = null;
            }
        }

        $schoolName = $settings->get('school_name', config('app.name', 'SMP Negeri 1 Biau'));
        $schoolAddress = $settings->get('school_address', '');
        $schoolCity = $settings->get('school_city', 'Buol');
        $headmasterName = $settings->get('school_headmaster_name', '');
        $headmasterNip = $settings->get('school_headmaster_nip', '');
        $homeroomTeacherName = $teacher->name;
        $homeroomTeacherNip = $teacher->nip;
        $printDate = now()->translatedFormat('d F Y, H:i:s');

        return view('teacher.print.attendance-report', compact(
            'class', 'students', 'attendances', 'period', 'selectedDate', 
            'attendanceSummary', 'totalHadir', 'totalSakit', 'totalIzin', 
            'totalAlpa', 'teacher', 'holidays', 'selfStudyDays',
            'paperSize', 'schoolName', 'schoolAddress', 'schoolCity', 'logoBase64',
            'headmasterName', 'headmasterNip', 'homeroomTeacherName', 'homeroomTeacherNip',
            'effectiveDaysCount', 'totalStudents', 'classAverageAttendance',
            'perfectAttendanceCount', 'needsAttentionCount', 'printDate'
        ));
    }

    public function printTrimesterAttendance(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $class = $teacher->homeroomClass;
        $request->validate([
            'trimester' => 'required|in:1,2,3,4',
            'year' => 'nullable|digits:4',
            'paper_size' => 'nullable|string|in:a4,folio,f4',
        ]);

        $trimester = (int)$request->input('trimester', 1);
        $year = (int)$request->input('year', date('Y'));
        $paperSize = strtolower($request->input('paper_size', 'a4'));

        switch ($trimester) {
            case 1:
                $months = [1, 2, 3];
                break;
            case 2:
                $months = [4, 5, 6];
                break;
            case 3:
                $months = [7, 8, 9];
                break;
            case 4:
                $months = [10, 11, 12];
                break;
        }

        $monthNames = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];

        $trimesterMap = [];
        foreach ($months as $m) {
            $effectiveDays = Setting::where('key', 'effective_days_' . $year . '_' . $m)->value('value');
            if ($effectiveDays === null) {
                $effectiveDays = Setting::where('key', 'effective_days_' . $m)->value('value');
            }
            
            if (empty($effectiveDays) || $effectiveDays == 0) {
                $startDate = Carbon::create($year, $m, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);
                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !\App\Models\Calendar::isDateInHolidays($d, $holidays);
                });
                $effectiveDays = $workdays->count();
            }

            $trimesterMap[$m] = [
                'name' => $monthNames[$m],
                'effective_days' => $effectiveDays !== null ? (int)$effectiveDays : 0
            ];
        }

        $students = Student::where('school_class_id', $class->id)
            ->with(['attendances' => function ($query) use ($year, $months) {
                $query->whereYear('attendance_time', $year)
                      ->whereIn(\DB::raw('MONTH(attendance_time)'), $months);
            }])
            ->orderBy('name')
            ->get();

        $reportData = $students->map(function ($student) use ($months, $trimesterMap, $year) {
            $studentData = [
                'name' => $student->name,
                'nis' => $student->nis,
                'monthly_data' => []
            ];

            foreach ($months as $m) {
                $attendancesInMonth = $student->attendances->filter(function ($att) use ($m) {
                    return Carbon::parse($att->attendance_time)->month == $m;
                });
                
                $startDate = Carbon::create($year, $m, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
                $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);

                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !\App\Models\Calendar::isDateInHolidays($d, $holidays);
                });

                $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0;

                foreach ($workdays as $wDate) {
                    $dateString = $wDate->format('Y-m-d');
                    $attendanceRecord = $attendancesInMonth->firstWhere(function($item) use ($dateString) {
                        return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                    });
                    
                    $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($wDate, $selfStudyDays);
                    
                    if ($isSelfStudy) {
                        $hadir++;
                    } else {
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        if (in_array($status, ['tepat_waktu', 'terlambat'])) $hadir++;
                        elseif ($status === 'sakit') $sakit++;
                        elseif (in_array($status, ['izin', 'izin_keluar'])) $izin++;
                        elseif ($status === 'alpa') $alpa++;
                    }
                }

                $effDays = $trimesterMap[$m]['effective_days'];
                $totalAbsen = $sakit + $izin + $alpa;
                $jml = $totalAbsen;
                if ($effDays > 0) {
                    $persen = (($effDays - $jml) / $effDays) * 100;
                    $persenStr = round($persen, 0) . '%';
                } else {
                    $persenStr = '0%';
                }

                $studentData['total_alpa'] = ($studentData['total_alpa'] ?? 0) + $alpa;
                $studentData['total_izin'] = ($studentData['total_izin'] ?? 0) + $izin;
                $studentData['total_sakit'] = ($studentData['total_sakit'] ?? 0) + $sakit;
                $studentData['total_jml'] = ($studentData['total_jml'] ?? 0) + $jml;
                $studentData['total_effective_days'] = ($studentData['total_effective_days'] ?? 0) + $effDays;

                $studentData['monthly_data'][$m] = [
                    'alpa' => $alpa,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'jml' => $jml,
                    'persen' => $persenStr
                ];
            }

            if (($studentData['total_effective_days'] ?? 0) > 0) {
                $totPersen = (($studentData['total_effective_days'] - $studentData['total_jml']) / $studentData['total_effective_days']) * 100;
                $studentData['total_persen'] = round($totPersen, 0) . '%';
                $studentData['total_persen_num'] = $totPersen;
            } else {
                $studentData['total_persen'] = '0%';
                $studentData['total_persen_num'] = 0;
            }

            return (object)$studentData;
        });

        $totalTrimesterEffectiveDays = 0;
        foreach ($months as $m) {
            $totalTrimesterEffectiveDays += $trimesterMap[$m]['effective_days'];
        }

        $totalStudents = $reportData->count();
        $classAverageAttendance = $totalStudents > 0 ? round($reportData->avg('total_persen_num'), 1) : 0;
        $perfectAttendanceCount = $reportData->filter(function($s) { return ($s->total_persen_num ?? 0) >= 100; })->count();
        $needsAttentionCount = $reportData->filter(function($s) { return ($s->total_persen_num ?? 0) < 85; })->count();

        $settings = Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            try {
                $logoData = Storage::disk('public')->get($logoPath);
                $logoBase64 = 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            } catch (\Exception $e) {
                $logoBase64 = null;
            }
        }

        $pdfData = [
            'schoolName' => $settings->get('school_name', config('app.name')),
            'schoolAddress' => $settings->get('school_address'),
            'schoolCity' => $settings->get('school_city', 'Buol'),
            'logoBase64' => $logoBase64,
            'appName' => config('app.name', 'SIASEK'),
            'printDate' => now()->translatedFormat('d F Y, H:i:s'),
            'userRole' => 'Wali Kelas',
            'reportData' => $reportData,
            'className' => $class->name,
            'trimester' => $trimester,
            'year' => $year,
            'trimesterMap' => $trimesterMap,
            'months' => $months,
            'totalTrimesterEffectiveDays' => $totalTrimesterEffectiveDays,
            'totalStudents' => $totalStudents,
            'classAverageAttendance' => $classAverageAttendance,
            'perfectAttendanceCount' => $perfectAttendanceCount,
            'needsAttentionCount' => $needsAttentionCount,
            'homeroomTeacherName' => $teacher->name,
            'homeroomTeacherNip' => $teacher->nip,
            'headmasterName' => $settings->get('school_headmaster_name'),
            'headmasterNip' => $settings->get('school_headmaster_nip'),
            'paperSize' => $paperSize,
        ];

        $pdf = Pdf::loadView('admin.reports.triwulan_pdf', $pdfData);
        if (in_array($paperSize, ['folio', 'f4'])) {
            $pdf->setPaper([0, 0, 935.43, 609.45], 'landscape');
        } else {
            $pdf->setPaper('a4', 'landscape');
        }

        return $pdf->stream('laporan-triwulan-' . $class->name . '-T' . $trimester . '-' . $year . '.pdf');
    }

    public function charts()
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $class = $teacher->homeroomClass;
        $students = Student::where('school_class_id', $class->id)->orderBy('name')->get();

        return view('teacher.attendance.charts', compact('class', 'students'));
    }

    public function chartData(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json(['error' => 'Akses tidak sah'], 403);
        }

        $class = $teacher->homeroomClass;
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'period' => 'required|in:weekly,monthly'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $studentId = $request->student_id;
        $periodType = $request->period;

        $students = Student::where('school_class_id', $class->id);
        if ($studentId && $studentId !== 'all') {
            $students->where('id', $studentId);
        }
        $students = $students->get();
        $studentIds = $students->pluck('id');
        $studentsCount = $students->count();

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
        $validDays = collect(CarbonPeriod::create($startDate, $endDate))->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !\App\Models\Calendar::isDateInHolidays($date, $holidays) && $date->startOfDay() <= now()->startOfDay();
        });

        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $totalHadir = $attendances->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
        $totalSakit = $attendances->where('status', 'sakit')->count();
        $totalIzin = $attendances->whereIn('status', ['izin', 'izin_keluar'])->count();
        $totalAlpaTercatat = $attendances->where('status', 'alpa')->count();

        $maxPossible = $validDays->count() * $studentsCount;
        $p_hadir = 0; $p_sakit = 0; $p_izin = 0; $p_alpa = 0;

        if ($maxPossible > 0) {
            $p_hadir = round(($totalHadir / $maxPossible) * 100, 1);
            $p_sakit = round(($totalSakit / $maxPossible) * 100, 1);
            $p_izin  = round(($totalIzin / $maxPossible) * 100, 1);
            
            $recorded = $totalHadir + $totalSakit + $totalIzin + $totalAlpaTercatat;
            $unrecorded = $maxPossible - $recorded;
            $totalAlpa = $totalAlpaTercatat + ($unrecorded > 0 ? $unrecorded : 0);
            
            $p_alpa  = round(($totalAlpa / $maxPossible) * 100, 1);
        }

        $summaryData = [
            'hadir' => $p_hadir,
            'sakit' => $p_sakit,
            'izin' => $p_izin,
            'alpa' => $p_alpa,
        ];

        $trendLabels = [];
        $trendData = [
            'hadir' => [],
            'sakit' => [],
            'izin' => [],
            'alpa' => []
        ];

        $groupedDays = [];
        foreach ($validDays as $day) {
            if ($periodType === 'weekly') {
                $label = 'Minggu ' . $day->weekOfMonth . ' ' . $day->translatedFormat('F');
            } else {
                $label = $day->translatedFormat('F Y');
            }
            if (!isset($groupedDays[$label])) {
                $groupedDays[$label] = [];
            }
            $groupedDays[$label][] = $day->format('Y-m-d');
        }

        foreach ($groupedDays as $label => $days) {
            $trendLabels[] = $label;
            $maxPossGroup = count($days) * $studentsCount;
            if ($maxPossGroup == 0) {
                $trendData['hadir'][] = 0; $trendData['sakit'][] = 0;
                $trendData['izin'][] = 0; $trendData['alpa'][] = 0;
                continue;
            }

            $gHadir = 0; $gSakit = 0; $gIzin = 0; $gAlpa = 0;
            foreach ($attendances as $att) {
                if (in_array(Carbon::parse($att->attendance_time)->format('Y-m-d'), $days)) {
                    if (in_array($att->status, ['tepat_waktu', 'terlambat'])) $gHadir++;
                    elseif ($att->status == 'sakit') $gSakit++;
                    elseif (in_array($att->status, ['izin', 'izin_keluar'])) $gIzin++;
                    elseif ($att->status == 'alpa') $gAlpa++;
                }
            }

            $gRecorded = $gHadir + $gSakit + $gIzin + $gAlpa;
            $gUnrecorded = $maxPossGroup - $gRecorded;
            $gAlpaTotal = $gAlpa + ($gUnrecorded > 0 ? $gUnrecorded : 0);

            $trendData['hadir'][] = round(($gHadir / $maxPossGroup) * 100, 1);
            $trendData['sakit'][] = round(($gSakit / $maxPossGroup) * 100, 1);
            $trendData['izin'][] = round(($gIzin / $maxPossGroup) * 100, 1);
            $trendData['alpa'][] = round(($gAlpaTotal / $maxPossGroup) * 100, 1);
        }

        return response()->json([
            'summary' => $summaryData,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData
        ]);
    }

    public function exportAttendanceExcel(Request $request)
    {
        $teacher = Auth::user()->teacher;

        if (!$teacher || !$teacher->homeroomClass) {
            return back()->with('error', 'Anda bukan wali kelas.');
        }

        $class = $teacher->homeroomClass;
        $students = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $selectedDate = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();

        $allAttendancesInMonth = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $attendances = $allAttendancesInMonth
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $period = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !\App\Models\Calendar::isDateInHolidays($date, $holidays);
        });

        $attendanceSummary = [];
        $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0;
        foreach ($students as $student) {
            $studentAttendances = $allAttendancesInMonth->where('student_id', $student->id);
            $hadirCount = 0; $sakitCount = 0; $izinCount = 0; $alpaCount = 0;
            
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $attendanceRecord = $studentAttendances->firstWhere(function($item) use ($dateString) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                });
                
                if ($attendanceRecord) {
                    if (in_array($attendanceRecord->status, ['tepat_waktu', 'terlambat'])) $hadirCount++;
                    elseif ($attendanceRecord->status === 'sakit') $sakitCount++;
                    elseif (in_array($attendanceRecord->status, ['izin', 'izin_keluar'])) $izinCount++;
                    elseif ($attendanceRecord->status === 'alpa') $alpaCount++;
                } else {
                    if ($date->isPast() && !$date->isToday()) {
                        $alpaCount++;
                    }
                }
            }
            $attendanceSummary[$student->id] = [
                'hadir' => $hadirCount,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
            ];
            $totalHadir += $hadirCount;
            $totalSakit += $sakitCount;
            $totalIzin += $izinCount;
            $totalAlpa += $alpaCount;
        }

        $settings = Setting::whereIn('key', [
            'app_logo', 'school_name', 'school_address', 'school_phone', 
            'school_email', 'school_headmaster_name', 'school_headmaster_nip'
        ])->get();
        
        $schoolIdentity = [
            'logo' => $settings->firstWhere('key', 'app_logo')->value ?? null,
            'name' => $settings->firstWhere('key', 'school_name')->value ?? null,
            'address' => $settings->firstWhere('key', 'school_address')->value ?? null,
            'phone' => $settings->firstWhere('key', 'school_phone')->value ?? null,
            'email' => $settings->firstWhere('key', 'school_email')->value ?? null,
            'headmaster_name' => $settings->firstWhere('key', 'school_headmaster_name')->value ?? null,
            'headmaster_nip' => $settings->firstWhere('key', 'school_headmaster_nip')->value ?? null,
        ];

        $fileName = 'Laporan_Presensi_' . str_replace(' ', '_', $class->name) . '_' . $selectedDate->format('F_Y') . '.xlsx';

        return Excel::download(new AttendanceReportExport(
            $class, $students, $attendances, $period, $selectedDate, 
            $attendanceSummary, $totalHadir, $totalSakit, $totalIzin, 
            $totalAlpa, $teacher, $schoolIdentity, $holidays, $selfStudyDays
        ), $fileName);
    }

    public function updateNote(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string|max:1000',
            'note' => 'nullable|string|max:1000',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Guru tidak ditemukan.');
        }

        $content = $request->input('content', $request->input('note'));

        TeacherNote::updateOrCreate(
            ['teacher_id' => $teacher->id],
            ['content' => $content]
        );

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
        }

        return back()->with('success', 'Catatan guru berhasil diperbarui.');
    }

    public function updateStudentPhoto(Request $request, Student $student)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher || !$teacher->homeroomClass || $teacher->homeroomClass->id !== $student->school_class_id) {
            return back()->with('error', 'Anda tidak berhak mengubah data siswa ini.');
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo);
            }

            $path = $request->file('photo')->store('students', 'public');
            $student->update([
                'photo' => $path,
                'face_descriptor' => null,
            ]);

            return back()->with('success', 'Foto ' . $student->name . ' berhasil diperbarui.');
        }

        return back()->with('error', 'Gagal mengunggah foto.');
    }
}
