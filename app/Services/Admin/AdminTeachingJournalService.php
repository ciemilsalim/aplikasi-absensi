<?php

namespace App\Services\Admin;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSemesterReflection;
use App\Models\TeachingJournal;
use Carbon\Carbon;

class AdminTeachingJournalService
{
    /**
     * Get supervision index data including journals pagination, lookup lists, and supervision metrics.
     */
    public function getSupervisionIndexData(array $filters = []): array
    {
        $teachers = Teacher::orderBy('name', 'asc')->get();
        $subjects = Subject::orderBy('name', 'asc')->get();
        $classes = SchoolClass::all()->sortBy('name', SORT_NATURAL)->values();

        $query = TeachingJournal::with(['teacher', 'subject', 'schoolClass', 'verifier']);

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['school_class_id'])) {
            $query->where('school_class_id', $filters['school_class_id']);
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
            ->paginate(20)
            ->withQueryString();

        // Metrics
        $totalJournals = TeachingJournal::count();
        $verifiedJournals = TeachingJournal::where('is_verified', true)->count();
        $unverifiedJournals = max(0, $totalJournals - $verifiedJournals);
        $activeTeachersCount = TeachingJournal::distinct('teacher_id')->count('teacher_id');

        return compact(
            'journals',
            'teachers',
            'subjects',
            'classes',
            'totalJournals',
            'verifiedJournals',
            'unverifiedJournals',
            'activeTeachersCount'
        );
    }

    /**
     * Get supervision detail for a specific teacher.
     */
    public function getTeacherSupervisionData(Teacher $teacher, array $filters = []): array
    {
        $subjects = $teacher->subjects()->orderBy('name', 'asc')->get();
        $classes = SchoolClass::all()->sortBy('name', SORT_NATURAL)->values();

        $query = TeachingJournal::with(['subject', 'schoolClass', 'verifier'])
            ->where('teacher_id', $teacher->id);

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['school_class_id'])) {
            $query->where('school_class_id', $filters['school_class_id']);
        }

        $journals = $query->orderBy('date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $reflection = TeacherSemesterReflection::where('teacher_id', $teacher->id)->latest()->first();

        $totalJp = TeachingJournal::where('teacher_id', $teacher->id)->sum('jp');
        $totalVerified = TeachingJournal::where('teacher_id', $teacher->id)->where('is_verified', true)->count();

        return compact(
            'teacher',
            'journals',
            'subjects',
            'classes',
            'reflection',
            'totalJp',
            'totalVerified'
        );
    }

    /**
     * Verify or unverify an individual teaching journal.
     */
    public function verifyJournal(TeachingJournal $journal, bool $isVerified, ?string $notes = null, ?int $verifierId = null): TeachingJournal
    {
        $journal->update([
            'is_verified' => $isVerified,
            'verified_by' => $isVerified ? $verifierId : null,
            'verified_at' => $isVerified ? now() : null,
            'supervisor_notes' => $notes,
        ]);

        return $journal;
    }

    /**
     * Mass verify teaching journals.
     */
    public function batchVerifyJournals(array $journalIds, ?string $supervisorNotes, int $verifierId): int
    {
        if (empty($journalIds)) {
            return 0;
        }

        return TeachingJournal::whereIn('id', $journalIds)->update([
            'is_verified' => true,
            'verified_by' => $verifierId,
            'verified_at' => now(),
            'supervisor_notes' => $supervisorNotes ?: 'Diverifikasi oleh Supervisor',
        ]);
    }
}
