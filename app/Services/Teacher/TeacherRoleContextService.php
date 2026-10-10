<?php

namespace App\Services\Teacher;

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
use App\Models\LeaveRequest;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherRoleContextService
{
    /**
     * Helper to get students of a class with fallbacks for pivot & active semester.
     */
    public static function getClassStudents($class, $relations = [])
    {
        if (!$class) {
            return collect();
        }

        $query = $class->students();
        if (!empty($relations)) {
            $query->with($relations);
        }
        $students = $query->orderBy('name')->get();

        if ($students->isEmpty() && Schema::hasTable('class_student')) {
            $studentIdsFromPivot = DB::table('class_student')
                ->where('school_class_id', $class->id)
                ->pluck('student_id')
                ->unique();

            if ($studentIdsFromPivot->isNotEmpty()) {
                $q = Student::whereIn('id', $studentIdsFromPivot);
                $activeSemesterId = session('active_semester_id') ?? \App\Models\Semester::where('is_active', true)->value('id');
                $isCurrentActiveSemester = $activeSemesterId ? (bool)\App\Models\Semester::where('id', $activeSemesterId)->value('is_active') : true;
                if ($isCurrentActiveSemester) {
                    $q->active();
                }
                if (!empty($relations)) {
                    $q->with($relations);
                }
                $students = $q->orderBy('name')->get();
            }
        }

        if ($students->isEmpty()) {
            $q = Student::where('school_class_id', $class->id);
            $activeSemesterId = session('active_semester_id') ?? \App\Models\Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool)\App\Models\Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            if (!empty($relations)) {
                $q->with($relations);
            }
            $students = $q->orderBy('name')->get();
        }

        return $students;
    }

    /**
     * Check if effective days have been configured for the current month.
     */
    public function checkEffectiveDaysSet(): bool
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        $effectiveDays = Setting::where('key', 'effective_days_' . $currentYear . '_' . $currentMonth)->value('value');
        if ($effectiveDays === null) {
            $effectiveDays = Setting::where('key', 'effective_days_' . $currentMonth)->value('value');
        }

        return !empty($effectiveDays) && $effectiveDays > 0;
    }

    /**
     * Build all view data required for teacher dashboard based on roles and active view tab.
     */
    public function buildDashboardViewData($teacher, ?string $requestedView): array
    {
        $viewData = ['teacher' => $teacher];

        $isHomeroomTeacher = $teacher->homeroomClass()->exists();
        $isSubjectTeacher = $teacher->teachingAssignments()->exists();
        $isExtracurricularCoach = $teacher->coachingExtracurriculars()->exists();
        $isCocurricularFacilitator = Schema::hasTable('cocurriculars')
            && ($teacher->cocurriculars()->exists() || Schedule::where('teacher_id', $teacher->id)->where('schedule_type', 'cocurricular')->exists());

        $hasAnyRole = $isHomeroomTeacher || $isSubjectTeacher || $isExtracurricularCoach || $isCocurricularFacilitator;
        if (!$hasAnyRole) {
            return [
                'hasRole' => false,
                'viewData' => $viewData,
            ];
        }

        if ($isHomeroomTeacher) {
            $defaultView = 'wali_kelas';
        } elseif ($isSubjectTeacher) {
            $defaultView = 'guru_mapel';
        } elseif ($isCocurricularFacilitator) {
            $defaultView = 'fasilitator_kokurikuler';
        } else {
            $defaultView = 'pembina_ekskul';
        }
        $currentView = $requestedView ?: $defaultView;

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

        // Ambil data presensi mandiri guru hari ini
        $teacherAttendanceToday = null;
        if (Schema::hasTable('teacher_attendances')) {
            try {
                $teacherAttendanceToday = TeacherAttendance::where('teacher_id', $teacher->id)
                    ->whereDate('created_at', Carbon::today())
                    ->first();
            } catch (\Throwable $e) {
                $teacherAttendanceToday = null;
            }
        }
        $viewData['teacherAttendanceToday'] = $teacherAttendanceToday;

        // Pending Leave Requests untuk wali kelas
        if ($isHomeroomTeacher && $teacher->homeroomClass) {
            try {
                $viewData['teacherPendingLeaveRequestsCount'] = LeaveRequest::whereHas('student', function ($q) use ($teacher) {
                    $q->where('school_class_id', $teacher->homeroomClass->id);
                })->where('status', 'pending')->count();
            } catch (\Throwable $e) {
                $viewData['teacherPendingLeaveRequestsCount'] = 0;
            }
        }

        // Ambil pengumuman terbaru
        $viewData['announcements'] = Announcement::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->take(3)
            ->get();

        return [
            'hasRole' => true,
            'viewData' => $viewData,
        ];
    }

    /**
     * Data aggregation for Homeroom Teacher (Wali Kelas).
     */
    public function getHomeroomData($teacher): array
    {
        $class = $teacher->homeroomClass;
        $today = Carbon::today();
        $thirtyDaysAgo = now()->subDays(30);

        $studentsInClass = self::getClassStudents($class);
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

        // Ambil 5 hari sekolah efektif terakhir (Senin-Jumat)
        $effectiveDates = [];
        $checkDate = now()->copy();
        $daysCounted = 0;
        while (count($effectiveDates) < 5 && $daysCounted < 14) {
            if (!$checkDate->isWeekend()) {
                $effectiveDates[] = $checkDate->copy();
            }
            $checkDate->subDay();
            $daysCounted++;
        }
        $effectiveDates = array_reverse($effectiveDates);

        $startDate = !empty($effectiveDates) ? $effectiveDates[0]->copy()->startOfDay() : now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $weeklyAttendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->get()
            ->groupBy(fn($date) => Carbon::parse($date->attendance_time)->format('Y-m-d'));

        $chartLabels = [];
        $chartData = [];
        $validPercentages = [];
        foreach ($effectiveDates as $date) {
            $chartLabels[] = $date->translatedFormat('d M');
            $dateString = $date->format('Y-m-d');
            $attendedCount = $weeklyAttendances->has($dateString) ? $weeklyAttendances[$dateString]->count() : 0;
            if ($totalStudents > 0) {
                if ($date->isToday() && $attendedCount === 0) {
                    $chartData[] = null;
                } else {
                    $percentage = round(($attendedCount / $totalStudents) * 100);
                    $chartData[] = $percentage;
                    $validPercentages[] = $percentage;
                }
            } else {
                $chartData[] = 0;
            }
        }

        $avgRate = count($validPercentages) > 0 ? round(array_sum($validPercentages) / count($validPercentages)) : 0;
        $trendInsight = $avgRate >= 80 ? 'Stabil di atas target 80%' : ($avgRate >= 60 ? 'Perlu ditingkatkan menuju target 80%' : 'Perlu perhatian khusus wali kelas');

        // Siswa yang perlu perhatian khusus
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
            'avgRate' => $avgRate,
            'trendInsight' => $trendInsight,
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

    /**
     * Data aggregation for Subject Teacher (Guru Mapel).
     */
    public function getSubjectTeacherData($teacher): array
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

        // Status kehadiran sesi per jadwal hari ini
        $attendedScheduleIdsToday = SubjectAttendance::whereIn('schedule_id', $schedulesToday->pluck('id'))
            ->whereDate('created_at', $now->toDateString())
            ->pluck('schedule_id')
            ->unique()
            ->toArray();

        $activeSchedule = null;
        $nextSchedule = null;
        $currentTime = $now->format('H:i:s');

        foreach ($schedulesToday as $sched) {
            $startTime = Carbon::parse($sched->start_time)->format('H:i:s');
            $endTime = Carbon::parse($sched->end_time)->format('H:i:s');
            $hasAttended = in_array($sched->id, $attendedScheduleIdsToday);
            $sched->has_attended_today = $hasAttended;

            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                $sched->status_time = 'ongoing';
                if (!$activeSchedule) {
                    $activeSchedule = $sched;
                }
            } elseif ($currentTime < $startTime) {
                $sched->status_time = 'upcoming';
                if (!$nextSchedule && !$activeSchedule) {
                    $nextSchedule = $sched;
                }
            } else {
                $sched->status_time = $hasAttended ? 'completed' : 'missed';
            }
        }

        $heroSchedule = $activeSchedule ?? $nextSchedule ?? $schedulesToday->first();
        $totalSessionsToday = $schedulesToday->count();
        $completedSessionsToday = count($attendedScheduleIdsToday);
        $remainingSessionsToday = max(0, $totalSessionsToday - $completedSessionsToday);

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
            if ($scheduleIds->isEmpty()) {
                continue;
            }
            $totalSessions = SubjectAttendance::whereIn('schedule_id', $scheduleIds)->where('created_at', '>=', $thirtyDaysAgo)->distinct(DB::raw('DATE(created_at)'))->count();
            $totalHadir = SubjectAttendance::whereIn('schedule_id', $scheduleIds)->where('status', 'hadir')->where('created_at', '>=', $thirtyDaysAgo)->count();
            $totalStudentsInClass = $assignment->getEnrolledStudents()->count();
            $potentialAttendance = $totalStudentsInClass * $totalSessions;
            $percentage = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100) : 0;
            $classPerformanceData[] = [
                'label' => ($assignment->schoolClass?->name ?? 'Kelas') . ' - ' . ($assignment->subject?->name ?? 'Mapel'),
                'className' => $assignment->schoolClass?->name ?? 'Kelas',
                'subjectName' => $assignment->subject?->name ?? 'Mapel',
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
            'heroSchedule' => $heroSchedule,
            'totalSessionsToday' => $totalSessionsToday,
            'completedSessionsToday' => $completedSessionsToday,
            'remainingSessionsToday' => $remainingSessionsToday,
            'attendedScheduleIdsToday' => $attendedScheduleIdsToday,
            'studentsForAttentionMapel' => $studentsForAttention,
            'lastAttendanceSummary' => $lastAttendanceSummary,
            'classPerformanceData' => $classPerformanceData,
            'teacherNote' => $teacherNote,
        ];
    }

    /**
     * Data aggregation for Cocurricular Facilitator (Fasilitator Kokurikuler).
     */
    public function getCocurricularFacilitatorData($teacher): array
    {
        $now = now();
        $dayOfWeekNumber = $now->dayOfWeek;

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

        // Status kehadiran sesi per jadwal kokurikuler hari ini
        $attendedScheduleIdsToday = SubjectAttendance::whereIn('schedule_id', $schedulesToday->pluck('id'))
            ->whereDate('created_at', $now->toDateString())
            ->pluck('schedule_id')
            ->unique()
            ->toArray();

        $activeSchedule = null;
        $nextSchedule = null;
        $currentTime = $now->format('H:i:s');

        foreach ($schedulesToday as $sched) {
            $startTime = Carbon::parse($sched->start_time)->format('H:i:s');
            $endTime = Carbon::parse($sched->end_time)->format('H:i:s');
            $hasAttended = in_array($sched->id, $attendedScheduleIdsToday);
            $sched->has_attended_today = $hasAttended;

            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                $sched->status_time = 'ongoing';
                if (!$activeSchedule) {
                    $activeSchedule = $sched;
                }
            } elseif ($currentTime < $startTime) {
                $sched->status_time = 'upcoming';
                if (!$nextSchedule && !$activeSchedule) {
                    $nextSchedule = $sched;
                }
            } else {
                $sched->status_time = $hasAttended ? 'completed' : 'missed';
            }
        }

        $heroSchedule = $activeSchedule ?? $nextSchedule ?? $schedulesToday->first();
        $totalSessionsToday = $schedulesToday->count();
        $completedSessionsToday = count($attendedScheduleIdsToday);
        $remainingSessionsToday = max(0, $totalSessionsToday - $completedSessionsToday);

        // Performa Kehadiran Kelas Kokurikuler 30 Hari Terakhir
        $classPerformanceData = [];
        $thirtyDaysAgo = now()->subDays(30);
        $totalSessions30Days = 0;
        $totalPercentageSum = 0;

        $distinctPairs = $allSchedules->unique(function ($item) {
            return $item->cocurricular_id . '-' . $item->school_class_id;
        });

        foreach ($distinctPairs as $item) {
            $pairScheduleIds = Schedule::where('schedule_type', 'cocurricular')
                ->where('cocurricular_id', $item->cocurricular_id)
                ->where('school_class_id', $item->school_class_id)
                ->pluck('id');

            if ($pairScheduleIds->isEmpty()) {
                continue;
            }

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

            $totalStudentsInClass = $item->schoolClass ? $item->schoolClass->students()->count() : Student::active()->where('school_class_id', $item->school_class_id)->count();
            $potentialAttendance = $totalStudentsInClass * $totalSessions;
            $percentage = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100) : 0;

            $totalSessions30Days += $totalSessions;
            $totalPercentageSum += $percentage;

            $classPerformanceData[] = [
                'label' => ($item->schoolClass?->name ?? 'Kelas') . ' - ' . \Illuminate\Support\Str::limit($item->cocurricular?->title ?? 'Kokurikuler', 25),
                'className' => $item->schoolClass?->name ?? 'Kelas',
                'title' => $item->cocurricular?->title ?? 'Kokurikuler',
                'percentage' => $percentage,
                'total_sessions' => $totalSessions,
            ];
        }

        $avgAttendance30Days = count($classPerformanceData) > 0 ? round($totalPercentageSum / count($classPerformanceData)) : 0;

        $myProjects = $teacher->cocurriculars()->with(['level', 'teachers'])->get();
        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'schedulesTodayKokurikuler' => $schedulesToday,
            'allSchedulesKokurikuler' => $allSchedules,
            'heroScheduleKokurikuler' => $heroSchedule,
            'totalSessionsTodayKokurikuler' => $totalSessionsToday,
            'completedSessionsTodayKokurikuler' => $completedSessionsToday,
            'remainingSessionsTodayKokurikuler' => $remainingSessionsToday,
            'totalSessions30DaysKokurikuler' => $totalSessions30Days,
            'avgAttendance30DaysKokurikuler' => $avgAttendance30Days,
            'studentsForAttentionKokurikuler' => $studentsForAttention,
            'lastAttendanceSummaryKokurikuler' => $lastAttendanceSummary,
            'classPerformanceDataKokurikuler' => $classPerformanceData,
            'myCocurricularProjects' => $myProjects,
            'teacherNote' => $teacherNote,
        ];
    }

    /**
     * Data aggregation for Extracurricular Coach (Pembina Ekskul).
     */
    public function getExtracurricularCoachData($teacher): array
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
        $totalSessions30Days = 0;
        $totalPercentageSum = 0;

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

            $totalSessions30Days += $totalSessions;
            $totalPercentageSum += $percentage;

            $classPerformanceDataEkskul[] = [
                'label' => $ekskul->name,
                'percentage' => $percentage,
                'total_sessions' => $totalSessions,
            ];
        }

        $avgAttendance30Days = count($classPerformanceDataEkskul) > 0 ? round($totalPercentageSum / count($classPerformanceDataEkskul)) : 0;

        // Riwayat sesi latihan terakhir (5 sesi terbaru)
        $recentPracticeSessions = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->select('extracurricular_id', 'attendance_date', DB::raw('count(*) as total_records'), DB::raw('SUM(CASE WHEN status="hadir" THEN 1 ELSE 0 END) as total_hadir'))
            ->groupBy('extracurricular_id', 'attendance_date')
            ->orderByDesc('attendance_date')
            ->with('extracurricular')
            ->take(5)
            ->get();

        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'coachedExtracurriculars' => $extracurriculars,
            'todayExtracurricularStats' => $todayAttendanceStats,
            'studentsForAttentionEkskul' => $studentsForAttentionEkskul,
            'lastAttendanceSummaryEkskul' => $lastAttendanceSummaryEkskul,
            'classPerformanceDataEkskul' => $classPerformanceDataEkskul,
            'recentPracticeSessions' => $recentPracticeSessions,
            'totalSessions30Days' => $totalSessions30Days,
            'avgAttendance30Days' => $avgAttendance30Days,
            'teacherNote' => $teacherNote,
        ];
    }
}
