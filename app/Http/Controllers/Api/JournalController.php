<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeachingJournal;
use App\Models\Schedule;
use Carbon\Carbon;

class JournalController extends Controller
{
    /**
     * Get journal history for the teacher.
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        $journals = TeachingJournal::where('teacher_id', $teacher->id)
            ->with(['schedule.teachingAssignment.subject', 'schedule.teachingAssignment.schoolClass'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date->format('Y-m-d'),
                    'subject_name' => $item->schedule->teachingAssignment->subject->name,
                    'class_name' => $item->schedule->teachingAssignment->schoolClass->name,
                    'material_content' => $item->material_content,
                    'notes' => $item->notes,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $journals
        ]);
    }

    /**
     * Store a new journal entry.
     */
    public function store(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'material_content' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $teacherId = $request->user()->teacher->id;

        $journal = TeachingJournal::updateOrCreate(
            [
                'schedule_id' => $request->schedule_id,
                'teacher_id' => $teacherId,
                'date' => Carbon::today(),
            ],
            [
                'material_content' => $request->material_content,
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal mengajar berhasil disimpan.',
            'data' => $journal
        ]);
    }

    /**
     * Get specific journal for a schedule today (to check if already filled).
     */
    public function showBySchedule(Request $request, $scheduleId)
    {
        $teacherId = $request->user()->teacher->id;
        $journal = TeachingJournal::where('schedule_id', $scheduleId)
            ->where('teacher_id', $teacherId)
            ->whereDate('date', Carbon::today())
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $journal
        ]);
    }
}
