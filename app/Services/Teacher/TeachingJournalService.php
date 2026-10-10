<?php

namespace App\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\SubjectAttendance;
use App\Models\Teacher;
use App\Models\TeacherSemesterReflection;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TeachingJournalService
{
    /**
     * Get data for journal index list and statistics.
     */
    public function getIndexData(Teacher $teacher, array $filters = []): array
    {
        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass')->unique('id')->filter()->sortBy('name', SORT_NATURAL)->values();
        $subjects = $assignments->pluck('subject')->unique('id')->filter()->sortBy('name', SORT_NATURAL)->values();

        $query = TeachingJournal::with([
            'schedule.teachingAssignment.schoolClass',
            'schedule.teachingAssignment.subject',
            'schoolClass',
            'subject',
            'verifier'
        ])->where('teacher_id', $teacher->id);

        if (!empty($filters['school_class_id'])) {
            $query->where('school_class_id', $filters['school_class_id']);
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['month'])) {
            $monthDate = Carbon::parse($filters['month']);
            $query->whereMonth('date', $monthDate->month)
                  ->whereYear('date', $monthDate->year);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'verified') {
                $query->where('is_verified', true);
            } elseif ($filters['status'] === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        $journals = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $totalJournals = TeachingJournal::where('teacher_id', $teacher->id)->count();
        $totalJp = TeachingJournal::where('teacher_id', $teacher->id)->sum('jp');
        $verifiedCount = TeachingJournal::where('teacher_id', $teacher->id)->where('is_verified', true)->count();
        $recentSchedules = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->get();

        return compact(
            'teacher',
            'journals',
            'classes',
            'subjects',
            'totalJournals',
            'totalJp',
            'verifiedCount',
            'recentSchedules'
        );
    }

    /**
     * Get data for journal creation form.
     */
    public function getCreateFormData(Teacher $teacher, ?int $selectedScheduleId = null, ?string $selectedDate = null): array
    {
        $schedules = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $selectedSchedule = $selectedScheduleId ? $schedules->firstWhere('id', $selectedScheduleId) : $schedules->first();
        $date = $selectedDate ?: Carbon::today()->toDateString();
        $estimatedJp = $this->estimateJpFromSchedule($selectedSchedule);
        $attendanceData = $this->calculateAttendanceSnapshot($selectedSchedule?->id, $date);

        return [
            'teacher' => $teacher,
            'schedules' => $schedules,
            'selectedSchedule' => $selectedSchedule,
            'selectedDate' => $date,
            'estimatedJp' => $estimatedJp,
            'attendanceData' => $attendanceData,
        ];
    }

    /**
     * Estimate JP from schedule duration.
     */
    public function estimateJpFromSchedule(?Schedule $schedule): int
    {
        if ($schedule && $schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);
            $diffMinutes = $start->diffInMinutes($end);
            return max(1, (int) round($diffMinutes / 40));
        }

        return 2;
    }

    /**
     * Calculate snapshot attendance counts for a schedule on a specific date.
     */
    public function calculateAttendanceSnapshot(?int $scheduleId, ?string $date): array
    {
        if (!$scheduleId) {
            return ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'total' => 0];
        }

        $attendances = SubjectAttendance::where('schedule_id', $scheduleId)
            ->whereDate('created_at', Carbon::parse($date))
            ->get();

        return [
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'alpa' => $attendances->whereIn('status', ['alpa', 'bolos'])->count(),
            'total' => $attendances->count(),
        ];
    }

    /**
     * Get API response data for schedule session.
     */
    public function getSessionData(Schedule $schedule, ?string $date): array
    {
        $resolvedDate = $date ?: Carbon::today()->toDateString();

        return [
            'schedule_id' => $schedule->id,
            'class_name' => $schedule->getTargetClass()?->name ?? '-',
            'subject_name' => $schedule->getActivityName(),
            'estimated_jp' => $this->estimateJpFromSchedule($schedule),
            'date' => $resolvedDate,
            'attendance' => $this->calculateAttendanceSnapshot($schedule->id, $resolvedDate),
        ];
    }

    /**
     * Persist a new teaching journal.
     */
    public function storeJournal(Teacher $teacher, Schedule $schedule, array $validated): TeachingJournal
    {
        $classId = $schedule->school_class_id ?: $schedule->teachingAssignment?->school_class_id;
        $subjectId = $schedule->teachingAssignment?->subject_id;
        $attSnapshot = $this->calculateAttendanceSnapshot($schedule->id, $validated['date']);

        return TeachingJournal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'school_class_id' => $classId,
            'subject_id' => $subjectId,
            'academic_year_id' => $schedule->academic_year_id,
            'semester_id' => $schedule->semester_id,
            'date' => $validated['date'],
            'jp' => $validated['jp'],
            'learning_objective' => $validated['learning_objective'],
            'topic' => $validated['topic'],
            'activity' => $validated['activity'],
            'assessment' => $validated['assessment'],
            'reflection' => $validated['reflection'],
            'follow_up' => $validated['follow_up'],
            'students_achieved_count' => $validated['students_achieved_count'] ?? null,
            'students_remedial_count' => $validated['students_remedial_count'] ?? null,
            'students_enrichment_count' => $validated['students_enrichment_count'] ?? null,
            'attendance_hadir' => $attSnapshot['hadir'] ?? 0,
            'attendance_sakit' => $attSnapshot['sakit'] ?? 0,
            'attendance_izin' => $attSnapshot['izin'] ?? 0,
            'attendance_alpa' => $attSnapshot['alpa'] ?? 0,
            'material_content' => $validated['topic'] . ' - ' . $validated['learning_objective'],
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    /**
     * Update an existing teaching journal.
     */
    public function updateJournal(TeachingJournal $journal, Schedule $schedule, array $validated): TeachingJournal
    {
        $classId = $schedule->school_class_id ?: $schedule->teachingAssignment?->school_class_id;
        $subjectId = $schedule->teachingAssignment?->subject_id;

        $journal->update([
            'schedule_id' => $schedule->id,
            'school_class_id' => $classId,
            'subject_id' => $subjectId,
            'date' => $validated['date'],
            'jp' => $validated['jp'],
            'learning_objective' => $validated['learning_objective'],
            'topic' => $validated['topic'],
            'activity' => $validated['activity'],
            'assessment' => $validated['assessment'],
            'reflection' => $validated['reflection'],
            'follow_up' => $validated['follow_up'],
            'students_achieved_count' => $validated['students_achieved_count'] ?? null,
            'students_remedial_count' => $validated['students_remedial_count'] ?? null,
            'students_enrichment_count' => $validated['students_enrichment_count'] ?? null,
            'material_content' => $validated['topic'] . ' - ' . $validated['learning_objective'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return $journal;
    }

    /**
     * Build weekly journal breakdown for reports and printing (DRY shared logic).
     */
    public function buildWeeklyBreakdown(Collection $journals, string $month, ?string $className = '-'): array
    {
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
        $currentStart = $startOfMonth->copy();
        $weekIndex = 1;
        $weeklyData = [];

        while ($currentStart->lte($endOfMonth)) {
            $currentEnd = $currentStart->copy()->endOfWeek(Carbon::SATURDAY);
            if ($currentEnd->gt($endOfMonth)) {
                $currentEnd = $endOfMonth->copy();
            }

            $weekJournals = $journals->filter(function ($j) use ($currentStart, $currentEnd) {
                $jDate = Carbon::parse($j->date);
                return $jDate->betweenIncluded($currentStart, $currentEnd);
            });

            $assessments = $weekJournals->pluck('assessment')->filter()->values();
            $assessmentDominant = $assessments->isNotEmpty()
                ? $assessments->countBy()->sortDesc()->keys()->first()
                : '-';

            $tpList = $weekJournals->pluck('learning_objective')->unique()->implode('; ');
            $followUps = $weekJournals->pluck('follow_up')->unique()->implode('; ');

            $weeklyData[] = [
                'week_number' => $weekIndex,
                'period' => $currentStart->translatedFormat('d M') . ' - ' . $currentEnd->translatedFormat('d M Y'),
                'class_name' => $className ?: '-',
                'meeting_count' => $weekJournals->count(),
                'total_jp' => $weekJournals->sum('jp'),
                'tp_conducted' => $tpList ?: '-',
                'dominant_assessment' => $assessmentDominant,
                'notes_follow_up' => $followUps ?: '-'
            ];

            $currentStart = $currentEnd->copy()->addDay()->startOfDay();
            $weekIndex++;
        }

        return $weeklyData;
    }

    /**
     * Get data for weekly report view.
     */
    public function getWeeklyReportData(Teacher $teacher, array $params = []): array
    {
        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass')->unique('id')->filter()->sortBy('name', SORT_NATURAL)->values();
        $subjects = $assignments->pluck('subject')->unique('id')->filter()->sortBy('name', SORT_NATURAL)->values();

        $selectedClassId = $params['school_class_id'] ?? $classes->first()?->id;
        $selectedSubjectId = $params['subject_id'] ?? $subjects->first()?->id;
        $selectedMonth = $params['month'] ?? Carbon::today()->format('Y-m');

        $startOfMonth = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($selectedMonth . '-01')->endOfMonth();

        $journals = TeachingJournal::where('teacher_id', $teacher->id)
            ->when($selectedClassId, fn($q) => $q->where('school_class_id', $selectedClassId))
            ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        $targetClass = SchoolClass::find($selectedClassId);
        $weeklyData = $this->buildWeeklyBreakdown($journals, $selectedMonth, $targetClass?->name ?? '-');

        return compact(
            'teacher',
            'classes',
            'subjects',
            'selectedClassId',
            'selectedSubjectId',
            'selectedMonth',
            'weeklyData'
        );
    }

    /**
     * Get data for semester report view.
     */
    public function getSemesterReportData(Teacher $teacher, ?int $selectedSubjectId = null): array
    {
        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass')->unique('id')->filter()->sortBy('name', SORT_NATURAL)->values();
        $subjects = $assignments->pluck('subject')->unique('id')->filter()->sortBy('name', SORT_NATURAL)->values();

        $subjectId = $selectedSubjectId ?: $subjects->first()?->id;
        $academicYear = AcademicYear::where('is_active', true)->first() ?: (object)['name' => '2026/2027'];
        $semester = Semester::where('is_active', true)->first() ?: (object)['name' => 'Ganjil'];

        $semesterClassData = [];
        $totalPlannedJp = 0;
        $totalActualJp = 0;

        foreach ($classes as $class) {
            $classJournals = TeachingJournal::where('teacher_id', $teacher->id)
                ->where('school_class_id', $class->id)
                ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
                ->get();

            $plannedJp = 36;
            $actualJp = $classJournals->sum('jp');
            $percentage = $plannedJp > 0 ? round(($actualJp / $plannedJp) * 100, 1) : 0;
            $uniqueTp = $classJournals->pluck('learning_objective')->unique()->filter()->count();

            $semesterClassData[] = [
                'class_name' => $class->name,
                'planned_jp' => $plannedJp,
                'actual_jp' => $actualJp,
                'percentage' => $percentage,
                'tp_done_count' => $uniqueTp,
                'tp_pending_count' => max(0, 10 - $uniqueTp),
                'notes' => $actualJp >= $plannedJp ? 'Target JP Tercapai' : 'Perlu Tambahan Jam'
            ];

            $totalPlannedJp += $plannedJp;
            $totalActualJp += $actualJp;
        }

        $assessmentJournals = TeachingJournal::with('schoolClass')
            ->where('teacher_id', $teacher->id)
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->orderBy('date', 'asc')
            ->get();

        return [
            'teacher' => $teacher,
            'classes' => $classes,
            'subjects' => $subjects,
            'selectedSubjectId' => $subjectId,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'semesterClassData' => $semesterClassData,
            'totalPlannedJp' => $totalPlannedJp,
            'totalActualJp' => $totalActualJp,
            'assessmentJournals' => $assessmentJournals,
        ];
    }

    /**
     * Persist teacher semester reflection.
     */
    public function storeSemesterReflection(Teacher $teacher, array $validated): TeacherSemesterReflection
    {
        return TeacherSemesterReflection::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'subject_id' => $validated['subject_id'] ?? null,
            ],
            $validated
        );
    }

    /**
     * Get data for printable official document view.
     */
    public function getPrintDocumentData(Teacher $teacher, ?int $schoolClassId = null, ?int $subjectId = null, ?string $month = null): array
    {
        $schoolClass = $schoolClassId ? SchoolClass::find($schoolClassId) : null;
        $subject = $subjectId ? Subject::find($subjectId) : $teacher->subjects()->first();

        $settings = Setting::all()->pluck('value', 'key');
        $schoolName = $settings['school_name'] ?? 'SMP Negeri 1 Biau';
        $schoolAddress = $settings['school_address'] ?? 'Jl. Syarief Mansyur No. 145 Kelurahan Leok II Kecamatan Biau Kabupaten Buol';
        $schoolCity = $settings['school_city'] ?? 'Buol';
        $principalName = $settings['school_headmaster_name'] ?? $settings['principal_name'] ?? '........................................................';
        $principalNip = $settings['school_headmaster_nip'] ?? $settings['principal_nip'] ?? '.....................................................';
        $schoolLogo = $settings['school_logo'] ?? $settings['app_logo'] ?? null;

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $activeSemester = Semester::where('is_active', true)->first();
        $academicYearName = $activeAcademicYear?->name ?? '2026/2027';
        $semesterName = $activeSemester?->name ?? 'Ganjil';

        $journals = TeachingJournal::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->when($schoolClassId, fn($q) => $q->where('school_class_id', $schoolClassId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($month, function ($q) use ($month) {
                $monthDate = Carbon::parse($month);
                $q->whereMonth('date', $monthDate->month)
                  ->whereYear('date', $monthDate->year);
            })
            ->orderBy('date', 'asc')
            ->get();

        $reflection = TeacherSemesterReflection::where('teacher_id', $teacher->id)
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->latest()
            ->first();

        $weeklyData = [];
        if ($month) {
            $weeklyData = $this->buildWeeklyBreakdown($journals, $month, $schoolClass?->name ?? 'Semua Kelas');
        }

        return compact(
            'teacher',
            'schoolClass',
            'subject',
            'journals',
            'weeklyData',
            'reflection',
            'schoolName',
            'schoolAddress',
            'schoolCity',
            'principalName',
            'principalNip',
            'schoolLogo',
            'academicYearName',
            'semesterName',
            'month'
        );
    }
}
