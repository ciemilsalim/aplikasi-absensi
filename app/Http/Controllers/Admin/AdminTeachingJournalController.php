<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Services\Admin\AdminTeachingJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTeachingJournalController extends Controller
{
    public function __construct(
        protected AdminTeachingJournalService $supervisionService
    ) {}

    /**
     * Tampilkan Halaman Supervisi Jurnal Mengajar Seluruh Guru
     */
    public function index(Request $request)
    {
        $data = $this->supervisionService->getSupervisionIndexData($request->all());

        return view('admin.teaching_journals.index', $data);
    }

    /**
     * Tampilkan Detail Jurnal & Rekap Supervisi per Guru
     */
    public function show(Teacher $teacher, Request $request)
    {
        $data = $this->supervisionService->getTeacherSupervisionData($teacher, $request->all());

        return view('admin.teaching_journals.show', $data);
    }

    /**
     * Aksi Supervisi & Verifikasi Jurnal oleh Admin / Waka
     */
    public function verify(Request $request, TeachingJournal $journal)
    {
        $validated = $request->validate([
            'is_verified' => 'required|boolean',
            'supervisor_notes' => 'nullable|string|max:1000',
        ]);

        $journal = $this->supervisionService->verifyJournal(
            $journal,
            (bool) $validated['is_verified'],
            $validated['supervisor_notes'] ?? null,
            Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $journal->is_verified ? 'Jurnal berhasil diverifikasi.' : 'Status verifikasi jurnal dibatalkan.',
                'journal' => $journal,
            ]);
        }

        return back()->with('success', 'Status supervisi jurnal berhasil diperbarui.');
    }

    /**
     * Verifikasi Massal (Batch Verify)
     */
    public function batchVerify(Request $request)
    {
        $validated = $request->validate([
            'journal_ids' => 'required|array',
            'journal_ids.*' => 'exists:teaching_journals,id',
            'supervisor_notes' => 'nullable|string|max:1000',
        ]);

        $count = $this->supervisionService->batchVerifyJournals(
            $validated['journal_ids'],
            $validated['supervisor_notes'] ?? null,
            Auth::id()
        );

        return back()->with('success', "{$count} jurnal berhasil diverifikasi.");
    }
}
