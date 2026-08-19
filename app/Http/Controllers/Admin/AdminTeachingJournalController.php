<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TeachingJournal;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\TeacherSemesterReflection;
use App\Models\Setting;
use Carbon\Carbon;

class AdminTeachingJournalController extends Controller
{
    /**
     * Tampilkan Halaman Supervisi Jurnal Mengajar Seluruh Guru
     */
    public function index(Request $request)
    {
        $teachers = Teacher::orderBy('name', 'asc')->get();
        $subjects = Subject::orderBy('name', 'asc')->get();
        $classes = SchoolClass::orderBy('name', 'asc')->get();

        $query = TeachingJournal::with(['teacher', 'subject', 'schoolClass', 'verifier']);

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
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
            ->paginate(20)
            ->withQueryString();

        // Metrik Supervisi
        $totalJournals = TeachingJournal::count();
        $verifiedJournals = TeachingJournal::where('is_verified', true)->count();
        $unverifiedJournals = $totalJournals - $verifiedJournals;
        $activeTeachersCount = TeachingJournal::distinct('teacher_id')->count('teacher_id');

        return view('admin.teaching_journals.index', compact(
            'journals',
            'teachers',
            'subjects',
            'classes',
            'totalJournals',
            'verifiedJournals',
            'unverifiedJournals',
            'activeTeachersCount'
        ));
    }

    /**
     * Tampilkan Detail Jurnal & Rekap Supervisi per Guru
     */
    public function show(Teacher $teacher, Request $request)
    {
        $subjects = $teacher->subjects()->get();
        $classes = SchoolClass::orderBy('name', 'asc')->get();

        $query = TeachingJournal::with(['subject', 'schoolClass', 'verifier'])
            ->where('teacher_id', $teacher->id);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        }

        $journals = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();

        $reflection = TeacherSemesterReflection::where('teacher_id', $teacher->id)->latest()->first();

        $totalJp = TeachingJournal::where('teacher_id', $teacher->id)->sum('jp');
        $totalVerified = TeachingJournal::where('teacher_id', $teacher->id)->where('is_verified', true)->count();

        return view('admin.teaching_journals.show', compact(
            'teacher',
            'journals',
            'subjects',
            'classes',
            'reflection',
            'totalJp',
            'totalVerified'
        ));
    }

    /**
     * Aksi Supervisi & Verifikasi Jurnal oleh Admin / Waka
     */
    public function verify(Request $request, TeachingJournal $journal)
    {
        $request->validate([
            'is_verified' => 'required|boolean',
            'supervisor_notes' => 'nullable|string|max:1000',
        ]);

        $journal->update([
            'is_verified' => $request->is_verified,
            'verified_by' => $request->is_verified ? Auth::id() : null,
            'verified_at' => $request->is_verified ? now() : null,
            'supervisor_notes' => $request->supervisor_notes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $request->is_verified ? 'Jurnal berhasil diverifikasi.' : 'Status verifikasi jurnal dibatalkan.',
                'journal' => $journal
            ]);
        }

        return back()->with('success', 'Status supervisi jurnal berhasil diperbarui.');
    }

    /**
     * Verifikasi Massal (Batch Verify)
     */
    public function batchVerify(Request $request)
    {
        $request->validate([
            'journal_ids' => 'required|array',
            'journal_ids.*' => 'exists:teaching_journals,id',
            'supervisor_notes' => 'nullable|string|max:1000',
        ]);

        TeachingJournal::whereIn('id', $request->journal_ids)->update([
            'is_verified' => true,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'supervisor_notes' => $request->supervisor_notes ?: 'Diverifikasi oleh Supervisor',
        ]);

        return back()->with('success', count($request->journal_ids) . ' jurnal berhasil diverifikasi.');
    }
}
