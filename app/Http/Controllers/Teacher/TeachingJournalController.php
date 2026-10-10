<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\TeacherSemesterReflection;
use App\Models\TeachingJournal;
use App\Services\Teacher\TeachingJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeachingJournalController extends Controller
{
    public function __construct(
        protected TeachingJournalService $journalService
    ) {}

    private function getTeacher()
    {
        return Auth::user()?->teacher;
    }

    public function index(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $viewData = $this->journalService->getIndexData($teacher, $request->all());

        return view('teacher.journals.index', $viewData);
    }

    public function create(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $viewData = $this->journalService->getCreateFormData(
            $teacher,
            $request->input('schedule_id'),
            $request->input('date')
        );

        return view('teacher.journals.create', $viewData);
    }

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

        if ($schedule->teachingAssignment?->teacher_id !== $teacher->id && $schedule->teacher_id !== $teacher->id) {
            return back()->with('error', 'Anda tidak memiliki akses ke jadwal ini.')->withInput();
        }

        $journal = $this->journalService->storeJournal($teacher, $schedule, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jurnal mengajar berhasil disimpan.',
                'journal' => $journal,
            ]);
        }

        return redirect()->route('teacher.journals.index')->with('success', 'Jurnal mengajar harian berhasil disimpan.');
    }

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
            ->orderBy('day_of_week', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('teacher.journals.edit', compact('teacher', 'journal', 'schedules'));
    }

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
        if ($schedule->teachingAssignment?->teacher_id !== $teacher->id && $schedule->teacher_id !== $teacher->id) {
            return back()->with('error', 'Anda tidak memiliki akses ke jadwal ini.')->withInput();
        }

        $this->journalService->updateJournal($journal, $schedule, $validated);

        return redirect()->route('teacher.journals.index')->with('success', 'Jurnal mengajar berhasil diperbarui.');
    }

    public function destroy(TeachingJournal $journal)
    {
        $teacher = $this->getTeacher();
        if (!$teacher || $journal->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.journals.index')->with('error', 'Akses ditolak.');
        }

        $journal->delete();
        return redirect()->route('teacher.journals.index')->with('success', 'Jurnal mengajar berhasil dihapus.');
    }

    public function apiGetSessionData(Request $request)
    {
        $scheduleId = $request->query('schedule_id');
        $date = $request->query('date');

        if (!$scheduleId) {
            return response()->json(['error' => 'Schedule ID required'], 400);
        }

        $schedule = Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])->find($scheduleId);
        if (!$schedule) {
            return response()->json(['error' => 'Jadwal tidak ditemukan'], 404);
        }

        return response()->json($this->journalService->getSessionData($schedule, $date));
    }

    public function weeklyReport(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $viewData = $this->journalService->getWeeklyReportData($teacher, $request->all());

        return view('teacher.journals.weekly_report', $viewData);
    }

    public function semesterReport(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $viewData = $this->journalService->getSemesterReportData($teacher, $request->input('subject_id'));

        return view('teacher.journals.semester_report', $viewData);
    }

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

        $this->journalService->storeSemesterReflection($teacher, $validated);

        return redirect()->route('teacher.journals.reflection', ['subject_id' => $validated['subject_id']])
            ->with('success', 'Refleksi guru semester berhasil disimpan.');
    }

    public function print(Request $request)
    {
        $teacher = $this->getTeacher();
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $viewData = $this->journalService->getPrintDocumentData(
            $teacher,
            $request->input('school_class_id'),
            $request->input('subject_id'),
            $request->input('month')
        );

        return view('teacher.journals.print', $viewData);
    }
}
