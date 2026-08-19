<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TeachingJournal;
use App\Models\TeacherSemesterReflection;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectAttendance;
use App\Models\Student;
use App\Models\Setting;
use App\Models\AcademicYear;
use App\Models\Semester;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TeachingJournalController extends Controller
{
    /**
     * Helper untuk mendapatkan data guru login
     */
    private function getTeacher()
    {
        return Auth::user()?->teacher;
    }

    /**
     * Tampilkan Daftar Jurnal Mengajar Guru
     */
    public function index(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        // Ambil penugasan mengajar aktif guru
        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass')->unique('id')->filter();
        $subjects = $assignments->pluck('subject')->unique('id')->filter();

        // Query Jurnal
        $query = TeachingJournal::with(['schedule.teachingAssignment.schoolClass', 'schedule.teachingAssignment.subject', 'schoolClass', 'subject', 'verifier'])
            ->where('teacher_id', $teacher->id);

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', Carbon::parse($request->month)->month)
                  ->whereYear('date', Carbon::parse($request->month)->year);
        }

        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($request->status === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        $journals = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Statistik Ringkas
        $totalJournals = TeachingJournal::where('teacher_id', $teacher->id)->count();
        $totalJp = TeachingJournal::where('teacher_id', $teacher->id)->sum('jp');
        $verifiedCount = TeachingJournal::where('teacher_id', $teacher->id)->where('is_verified', true)->count();
        $recentSchedules = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->get();

        return view('teacher.journals.index', compact(
            'teacher',
            'journals',
            'classes',
            'subjects',
            'totalJournals',
            'totalJp',
            'verifiedCount',
            'recentSchedules'
        ));
    }

    /**
     * Form Tambah Jurnal Harian
     */
    public function create(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        // Ambil jadwal reguler guru
        $schedules = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->get();

        $selectedScheduleId = $request->input('schedule_id');
        $selectedSchedule = $selectedScheduleId ? $schedules->firstWhere('id', $selectedScheduleId) : $schedules->first();
        $selectedDate = $request->input('date', Carbon::today()->toDateString());

        // Hitung estimasi JP dari durasi jadwal
        $estimatedJp = 2;
        if ($selectedSchedule && $selectedSchedule->start_time && $selectedSchedule->end_time) {
            $start = Carbon::parse($selectedSchedule->start_time);
            $end = Carbon::parse($selectedSchedule->end_time);
            $diffMinutes = $start->diffInMinutes($end);
            $estimatedJp = max(1, (int) round($diffMinutes / 40));
        }

        // Snapshot presensi siswa dari sesi jadwal & tanggal jika ada
        $attendanceData = $this->calculateAttendanceSnapshot($selectedSchedule?->id, $selectedDate);

        return view('teacher.journals.create', compact(
            'teacher',
            'schedules',
            'selectedSchedule',
            'selectedDate',
            'estimatedJp',
            'attendanceData'
        ));
    }

    /**
     * Simpan Jurnal Harian
     */
    public function store(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date',
            'jp' => 'required|integer|min:1|max:10',
            'learning_objective' => 'required|string|max:2000',
            'topic' => 'required|string|max:255',
            'activity' => 'required|string|max:3000',
            'assessment' => 'required|string|max:255',
            'reflection' => 'required|string|max:3000',
            'follow_up' => 'required|string|max:2000',
            'students_achieved_count' => 'nullable|integer|min:0',
            'students_remedial_count' => 'nullable|integer|min:0',
            'students_enrichment_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $schedule = Schedule::with('teachingAssignment')->findOrFail($validated['schedule_id']);
        
        // Cek otorisasi guru
        if ($schedule->teachingAssignment?->teacher_id !== $teacher->id && $schedule->teacher_id !== $teacher->id) {
            return back()->with('error', 'Anda tidak memiliki akses ke jadwal ini.')->withInput();
        }

        $classId = $schedule->school_class_id ?: $schedule->teachingAssignment?->school_class_id;
        $subjectId = $schedule->teachingAssignment?->subject_id;

        // Auto snapshot presensi siswa jika belum diisi manual
        $attSnapshot = $this->calculateAttendanceSnapshot($schedule->id, $validated['date']);

        $journal = TeachingJournal::create([
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jurnal mengajar berhasil disimpan.',
                'journal' => $journal
            ]);
        }

        return redirect()->route('teacher.journals.index')->with('success', 'Jurnal mengajar harian berhasil disimpan.');
    }

    /**
     * Form Edit Jurnal
     */
    public function edit(TeachingJournal $journal)
    {
        $teacher = $this->getTeacher();
        if (!$teacher || $journal->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.journals.index')->with('error', 'Akses ditolak.');
        }

        $schedules = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->get();

        return view('teacher.journals.edit', compact('teacher', 'journal', 'schedules'));
    }

    /**
     * Update Jurnal
     */
    public function update(Request $request, TeachingJournal $journal)
    {
        $teacher = $this->getTeacher();
        if (!$teacher || $journal->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.journals.index')->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date' => 'required|date',
            'jp' => 'required|integer|min:1|max:10',
            'learning_objective' => 'required|string|max:2000',
            'topic' => 'required|string|max:255',
            'activity' => 'required|string|max:3000',
            'assessment' => 'required|string|max:255',
            'reflection' => 'required|string|max:3000',
            'follow_up' => 'required|string|max:2000',
            'students_achieved_count' => 'nullable|integer|min:0',
            'students_remedial_count' => 'nullable|integer|min:0',
            'students_enrichment_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $schedule = Schedule::with('teachingAssignment')->findOrFail($validated['schedule_id']);
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

        return redirect()->route('teacher.journals.index')->with('success', 'Jurnal mengajar berhasil diperbarui.');
    }

    /**
     * Hapus Jurnal
     */
    public function destroy(TeachingJournal $journal)
    {
        $teacher = $this->getTeacher();
        if (!$teacher || $journal->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.journals.index')->with('error', 'Akses ditolak.');
        }

        $journal->delete();
        return redirect()->route('teacher.journals.index')->with('success', 'Jurnal mengajar berhasil dihapus.');
    }

    /**
     * API untuk mengambil data snapshot presensi & info sesi
     */
    public function apiGetSessionData(Request $request)
    {
        $scheduleId = $request->query('schedule_id');
        $date = $request->query('date', Carbon::today()->toDateString());

        if (!$scheduleId) {
            return response()->json(['error' => 'Schedule ID required'], 400);
        }

        $schedule = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])->find($scheduleId);
        if (!$schedule) {
            return response()->json(['error' => 'Jadwal tidak ditemukan'], 404);
        }

        $estimatedJp = 2;
        if ($schedule->start_time && $schedule->end_time) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);
            $diffMinutes = $start->diffInMinutes($end);
            $estimatedJp = max(1, (int) round($diffMinutes / 40));
        }

        $attendance = $this->calculateAttendanceSnapshot($scheduleId, $date);

        return response()->json([
            'schedule_id' => $schedule->id,
            'class_name' => $schedule->getTargetClass()?->name ?? '-',
            'subject_name' => $schedule->getActivityName(),
            'estimated_jp' => $estimatedJp,
            'date' => $date,
            'attendance' => $attendance
        ]);
    }

    /**
     * Tampilan Rekap Mingguan (Bagian C)
     */
    public function weeklyReport(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass')->unique('id')->filter();
        $subjects = $assignments->pluck('subject')->unique('id')->filter();

        $selectedClassId = $request->input('school_class_id', $classes->first()?->id);
        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);
        $selectedMonth = $request->input('month', Carbon::today()->format('Y-m'));

        $startOfMonth = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($selectedMonth . '-01')->endOfMonth();

        // Ambil semua jurnal pada rentang bulan tersebut
        $journals = TeachingJournal::where('teacher_id', $teacher->id)
            ->when($selectedClassId, fn($q) => $q->where('school_class_id', $selectedClassId))
            ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        // Bagi data per minggu kalender
        $weeklyData = [];
        $currentStart = $startOfMonth->copy();
        $weekIndex = 1;

        while ($currentStart->lte($endOfMonth)) {
            $currentEnd = $currentStart->copy()->endOfWeek(Carbon::SATURDAY);
            if ($currentEnd->gt($endOfMonth)) {
                $currentEnd = $endOfMonth->copy();
            }

            $weekJournals = $journals->filter(function ($j) use ($currentStart, $currentEnd) {
                $jDate = Carbon::parse($j->date);
                return $jDate->betweenIncluded($currentStart, $currentEnd);
            });

            // Asesmen dominan
            $assessments = $weekJournals->pluck('assessment')->filter()->values();
            $assessmentDominant = $assessments->isNotEmpty() 
                ? $assessments->countBy()->sortDesc()->keys()->first() 
                : '-';

            // Gabungan TP
            $tpList = $weekJournals->pluck('learning_objective')->unique()->implode('; ');
            $followUps = $weekJournals->pluck('follow_up')->unique()->implode('; ');

            $weeklyData[] = [
                'week_number' => $weekIndex,
                'period' => $currentStart->translatedFormat('d M') . ' - ' . $currentEnd->translatedFormat('d M Y'),
                'class_name' => SchoolClass::find($selectedClassId)?->name ?? '-',
                'meeting_count' => $weekJournals->count(),
                'total_jp' => $weekJournals->sum('jp'),
                'tp_conducted' => $tpList ?: '-',
                'dominant_assessment' => $assessmentDominant,
                'notes_follow_up' => $followUps ?: '-'
            ];

            $currentStart = $currentEnd->copy()->addDay()->startOfDay();
            $weekIndex++;
        }

        return view('teacher.journals.weekly_report', compact(
            'teacher',
            'classes',
            'subjects',
            'selectedClassId',
            'selectedSubjectId',
            'selectedMonth',
            'weeklyData'
        ));
    }

    /**
     * Tampilan Rekap Semester & Asesmen (Bagian D & E)
     */
    public function semesterReport(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass')->unique('id')->filter();
        $subjects = $assignments->pluck('subject')->unique('id')->filter();

        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);
        $academicYear = AcademicYear::where('is_active', true)->first() ?: (object)['name' => '2026/2027'];
        $semester = Semester::where('is_active', true)->first() ?: (object)['name' => 'Ganjil'];

        // Rekap per Kelas (Bagian D)
        $semesterClassData = [];
        $totalPlannedJp = 0;
        $totalActualJp = 0;

        foreach ($classes as $class) {
            $classJournals = TeachingJournal::where('teacher_id', $teacher->id)
                ->where('school_class_id', $class->id)
                ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
                ->get();

            // Standar JP semester: misal 2 JP x 18 minggu = 36 JP direncanakan
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
                'tp_pending_count' => max(0, 10 - $uniqueTp), // estimasi 10 TP per semester
                'notes' => $actualJp >= $plannedJp ? 'Target JP Tercapai' : 'Perlu Tambahan Jam'
            ];

            $totalPlannedJp += $plannedJp;
            $totalActualJp += $actualJp;
        }

        // Rekap Asesmen & Tindak Lanjut (Bagian E)
        $assessmentJournals = TeachingJournal::with('schoolClass')
            ->where('teacher_id', $teacher->id)
            ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
            ->orderBy('date', 'asc')
            ->get();

        return view('teacher.journals.semester_report', compact(
            'teacher',
            'classes',
            'subjects',
            'selectedSubjectId',
            'academicYear',
            'semester',
            'semesterClassData',
            'totalPlannedJp',
            'totalActualJp',
            'assessmentJournals'
        ));
    }

    /**
     * Form Input & Tampilan Refleksi Guru Semester (Bagian F)
     */
    public function reflection(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $subjects = $teacher->subjects()->get();
        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);

        $reflection = TeacherSemesterReflection::where('teacher_id', $teacher->id)
            ->when($selectedSubjectId, fn($q) => $q->where('subject_id', $selectedSubjectId))
            ->latest()
            ->first();

        return view('teacher.journals.reflection', compact('teacher', 'subjects', 'selectedSubjectId', 'reflection'));
    }

    /**
     * Simpan Refleksi Guru Semester (Bagian F)
     */
    public function storeReflection(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $validated = $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'good_aspects' => 'required|string|max:3000',
            'challenges' => 'required|string|max:3000',
            'attention_students' => 'required|string|max:3000',
            'effective_strategies' => 'required|string|max:3000',
            'future_improvements' => 'required|string|max:3000',
            'follow_up_plan' => 'required|string|max:3000',
        ]);

        TeacherSemesterReflection::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'subject_id' => $validated['subject_id'] ?? null,
            ],
            $validated
        );

        return redirect()->route('teacher.journals.reflection', ['subject_id' => $validated['subject_id']])
            ->with('success', 'Refleksi guru semester berhasil disimpan.');
    }

    /**
     * Lembar Cetak Dokumen Resmi Lengkap (Bagian A, B, C, D, E, F, G)
     */
    public function print(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $schoolClassId = $request->input('school_class_id');
        $subjectId = $request->input('subject_id');
        $month = $request->input('month');

        $schoolClass = $schoolClassId ? SchoolClass::find($schoolClassId) : null;
        $subject = $subjectId ? Subject::find($subjectId) : $teacher->subjects()->first();
        
        $setting = Setting::first();
        $schoolName = $setting?->school_name ?: 'SMP NEGERI 1 BIAU';
        $principalName = $setting?->principal_name ?: '........................................................';
        $principalNip = $setting?->principal_nip ?: '.....................................................';

        // Query Jurnal Harian (Bagian B)
        $journals = TeachingJournal::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->when($schoolClassId, fn($q) => $q->where('school_class_id', $schoolClassId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($month, function ($q) use ($month) {
                $q->whereMonth('date', Carbon::parse($month)->month)
                  ->whereYear('date', Carbon::parse($month)->year);
            })
            ->orderBy('date', 'asc')
            ->get();

        // Refleksi Semester (Bagian F)
        $reflection = TeacherSemesterReflection::where('teacher_id', $teacher->id)
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->latest()
            ->first();

        // Format Mingguan & Semester
        $weeklyData = [];
        if ($month) {
            $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
            $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
            $currentStart = $startOfMonth->copy();
            $wIdx = 1;

            while ($currentStart->lte($endOfMonth)) {
                $currentEnd = $currentStart->copy()->endOfWeek(Carbon::SATURDAY);
                if ($currentEnd->gt($endOfMonth)) {
                    $currentEnd = $endOfMonth->copy();
                }

                $wJournals = $journals->filter(function ($j) use ($currentStart, $currentEnd) {
                    $jDate = Carbon::parse($j->date);
                    return $jDate->betweenIncluded($currentStart, $currentEnd);
                });

                $assessments = $wJournals->pluck('assessment')->filter()->values();
                $assessmentDominant = $assessments->isNotEmpty() ? $assessments->countBy()->sortDesc()->keys()->first() : '-';

                $weeklyData[] = [
                    'week_number' => $wIdx,
                    'period' => $currentStart->translatedFormat('d M') . ' - ' . $currentEnd->translatedFormat('d M Y'),
                    'class_name' => $schoolClass?->name ?? 'Semua Kelas',
                    'meeting_count' => $wJournals->count(),
                    'total_jp' => $wJournals->sum('jp'),
                    'tp_conducted' => $wJournals->pluck('learning_objective')->unique()->implode('; ') ?: '-',
                    'dominant_assessment' => $assessmentDominant,
                    'notes_follow_up' => $wJournals->pluck('follow_up')->unique()->implode('; ') ?: '-'
                ];

                $currentStart = $currentEnd->copy()->addDay()->startOfDay();
                $wIdx++;
            }
        }

        return view('teacher.journals.print', compact(
            'teacher',
            'schoolClass',
            'subject',
            'journals',
            'weeklyData',
            'reflection',
            'schoolName',
            'principalName',
            'principalNip',
            'month'
        ));
    }

    /**
     * Helper privat menghitung snapshot presensi siswa
     */
    private function calculateAttendanceSnapshot($scheduleId, $date)
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
}
