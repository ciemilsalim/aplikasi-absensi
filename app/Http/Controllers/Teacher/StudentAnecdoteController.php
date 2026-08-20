<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentAnecdote;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Setting;
use Carbon\Carbon;

class StudentAnecdoteController extends Controller
{
    /**
     * Memeriksa otorisasi guru terhadap jadwal.
     */
    private function isTeacherAuthorized(?Schedule $schedule, $teacher): bool
    {
        if (!$schedule) {
            return true;
        }

        if ($schedule->isCocurricular()) {
            if ($schedule->teacher_id === $teacher->id) {
                return true;
            }
            $schedule->loadMissing('cocurricular.teachers');
            return $schedule->cocurricular && $schedule->cocurricular->teachers->contains('id', $teacher->id);
        }

        $schedule->loadMissing('teachingAssignment');
        return $schedule->teachingAssignment && $schedule->teachingAssignment->teacher_id === $teacher->id;
    }

    /**
     * Mengambil data catatan anekdot siswa untuk jadwal & tanggal tertentu (AJAX).
     */
    public function getForStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'schedule_id' => 'nullable|exists:schedules,id',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $student = Student::with('schoolClass')->find($request->student_id);
        $date = Carbon::parse($request->date)->format('Y-m-d');
        $scheduleId = $request->schedule_id;

        try {
            $query = StudentAnecdote::where('student_id', $student->id)
                ->whereDate('date', $date);

            if ($scheduleId) {
                $query->where('schedule_id', $scheduleId);
            } else {
                $query->where('teacher_id', $teacher->id);
            }

            $anecdote = $query->first();

            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'class_name' => $student->schoolClass?->name ?? '-',
                    'photo_url' => $student->photo_url,
                ],
                'anecdote' => $anecdote ? [
                    'id' => $anecdote->id,
                    'academic_note' => $anecdote->academic_note ?? '',
                    'academic_sentiment' => $anecdote->academic_sentiment ?? 'neutral',
                    'attendance_note' => $anecdote->attendance_note ?? '',
                    'attendance_sentiment' => $anecdote->attendance_sentiment ?? 'neutral',
                    'attitude_note' => $anecdote->attitude_note ?? '',
                    'attitude_sentiment' => $anecdote->attitude_sentiment ?? 'neutral',
                    'follow_up' => $anecdote->follow_up ?? '',
                    'is_visible_to_parents' => (bool)$anecdote->is_visible_to_parents,
                    'date' => $anecdote->date->format('Y-m-d'),
                ] : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'class_name' => $student->schoolClass?->name ?? '-',
                    'photo_url' => $student->photo_url,
                ],
                'anecdote' => null,
            ]);
        }
    }

    /**
     * Menyimpan atau memperbarui catatan anekdot siswa (AJAX).
     */
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'schedule_id' => 'nullable|exists:schedules,id',
            'academic_note' => 'nullable|string|max:2000',
            'academic_sentiment' => 'nullable|in:positive,neutral,needs_guidance',
            'attendance_note' => 'nullable|string|max:2000',
            'attendance_sentiment' => 'nullable|in:positive,neutral,needs_guidance',
            'attitude_note' => 'nullable|string|max:2000',
            'attitude_sentiment' => 'nullable|in:positive,neutral,needs_guidance',
            'follow_up' => 'nullable|string|max:2000',
            'is_visible_to_parents' => 'nullable|boolean',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $student = Student::find($request->student_id);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $schedule = null;
        $subjectId = null;
        $schoolClassId = $student->school_class_id;

        if ($request->filled('schedule_id')) {
            $schedule = Schedule::with(['teachingAssignment', 'cocurricular.teachers'])->find($request->schedule_id);
            if ($schedule) {
                if (!$this->isTeacherAuthorized($schedule, $teacher)) {
                    return response()->json(['success' => false, 'message' => 'Otorisasi jadwal gagal.'], 403);
                }
                $subjectId = $schedule->teachingAssignment?->subject_id;
                $schoolClassId = $schedule->getTargetClass()?->id ?? $schoolClassId;
            }
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');

        // Cari atau buat baru
        $query = StudentAnecdote::where('student_id', $student->id)
            ->whereDate('date', $date);

        if ($schedule) {
            $query->where('schedule_id', $schedule->id);
        } else {
            $query->where('teacher_id', $teacher->id);
        }

        $anecdote = $query->first();

        $data = [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'schedule_id' => $schedule?->id,
            'subject_id' => $subjectId,
            'school_class_id' => $schoolClassId,
            'date' => $date,
            'academic_note' => $request->academic_note,
            'academic_sentiment' => $request->academic_sentiment ?? 'neutral',
            'attendance_note' => $request->attendance_note,
            'attendance_sentiment' => $request->attendance_sentiment ?? 'neutral',
            'attitude_note' => $request->attitude_note,
            'attitude_sentiment' => $request->attitude_sentiment ?? 'neutral',
            'follow_up' => $request->follow_up,
            'is_visible_to_parents' => $request->boolean('is_visible_to_parents'),
        ];

        try {
            if ($anecdote) {
                $anecdote->update($data);
            } else {
                $anecdote = StudentAnecdote::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Catatan anekdot untuk ' . $student->name . ' berhasil disimpan.',
                'anecdote' => [
                    'id' => $anecdote->id,
                    'student_id' => $student->id,
                    'has_notes' => $anecdote->hasAnyNote(),
                    'academic_sentiment' => $anecdote->academic_sentiment,
                    'attendance_sentiment' => $anecdote->attendance_sentiment,
                    'attitude_sentiment' => $anecdote->attitude_sentiment,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan ke basis data: pastikan migrasi database telah dijalankan.'
            ], 500);
        }
    }

    /**
     * Menampilkan halaman rekapitulasi daftar catatan anekdot.
     */
    public function index(Request $request)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        // Pilihan kelas & mapel yang diajar
        $assignments = TeachingAssignment::with(['schoolClass', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes = $assignments->pluck('schoolClass.name', 'schoolClass.id')->filter()->unique();
        $subjects = $assignments->pluck('subject.name', 'subject.id')->filter()->unique();

        // Cek jika guru adalah wali kelas
        $homeroomClass = $teacher->homeroomClass;
        if ($homeroomClass && !isset($classes[$homeroomClass->id])) {
            $classes[$homeroomClass->id] = $homeroomClass->name . ' (Wali Kelas)';
        }

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::today()->subDays(30);
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::today();

        $query = StudentAnecdote::with(['student.schoolClass', 'teacher', 'subject', 'schedule'])
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Filter berdasarkan peran (wali kelas bisa melihat semua catatan di kelasnya, guru mapel melihat catatan buatannya)
        $selectedClassId = $request->school_class_id;
        $selectedSubjectId = $request->subject_id;
        $selectedSentiment = $request->sentiment;
        $search = $request->search;

        $isHomeroomForSelected = $homeroomClass && $selectedClassId == $homeroomClass->id;

        if ($selectedClassId) {
            $query->where('school_class_id', $selectedClassId);
            if (!$isHomeroomForSelected) {
                $query->where('teacher_id', $teacher->id);
            }
        } else {
            // Default: catatan yang dibuat guru ini, atau di kelas perwaliannya
            $query->where(function ($q) use ($teacher, $homeroomClass) {
                $q->where('teacher_id', $teacher->id);
                if ($homeroomClass) {
                    $q->orWhere('school_class_id', $homeroomClass->id);
                }
            });
        }

        if ($selectedSubjectId) {
            $query->where('subject_id', $selectedSubjectId);
        }

        if ($selectedSentiment) {
            $query->where(function ($q) use ($selectedSentiment) {
                $q->where('academic_sentiment', $selectedSentiment)
                  ->orWhere('attendance_sentiment', $selectedSentiment)
                  ->orWhere('attitude_sentiment', $selectedSentiment);
            });
        }

        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        try {
            $anecdotes = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();
        } catch (\Throwable $e) {
            $anecdotes = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return view('teacher.anecdotes.index', compact(
            'anecdotes',
            'classes',
            'subjects',
            'startDate',
            'endDate',
            'selectedClassId',
            'selectedSubjectId',
            'selectedSentiment',
            'search',
            'homeroomClass'
        ));
    }

    /**
     * Menampilkan halaman cetak rekap lembar catatan anekdot.
     */
    public function print(Request $request)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::today()->subDays(30);
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::today();
        $selectedClassId = $request->school_class_id;
        $selectedSubjectId = $request->subject_id;

        $classInfo = $selectedClassId ? SchoolClass::find($selectedClassId) : null;
        $subjectInfo = $selectedSubjectId ? Subject::find($selectedSubjectId) : null;

        $query = StudentAnecdote::with(['student', 'teacher', 'subject', 'schoolClass'])
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($selectedClassId) {
            $query->where('school_class_id', $selectedClassId);
        }
        if ($selectedSubjectId) {
            $query->where('subject_id', $selectedSubjectId);
        }

        $homeroomClass = $teacher->homeroomClass;
        $isHomeroomForSelected = $homeroomClass && $selectedClassId == $homeroomClass->id;
        if (!$isHomeroomForSelected) {
            $query->where('teacher_id', $teacher->id);
        }

        try {
            $anecdotes = $query->orderBy('date', 'asc')->get();
        } catch (\Throwable $e) {
            $anecdotes = collect();
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

        return view('teacher.anecdotes.print', compact(
            'anecdotes',
            'classInfo',
            'subjectInfo',
            'startDate',
            'endDate',
            'schoolIdentity',
            'teacher'
        ));
    }

    /**
     * Menghapus catatan anekdot.
     */
    public function destroy(StudentAnecdote $anecdote)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher || ($anecdote->teacher_id !== $teacher->id && !Auth::user()->isAdmin())) {
            return back()->with('error', 'Anda tidak memiliki hak untuk menghapus catatan ini.');
        }

        $anecdote->delete();
        return back()->with('success', 'Catatan anekdot berhasil dihapus.');
    }
}
