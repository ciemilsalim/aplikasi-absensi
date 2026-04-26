<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SubjectAttendance;
use App\Models\Schedule;
use App\Models\TeachingJournal;
use App\Models\TeacherNote;
use Illuminate\Support\Facades\Auth;

class ParentStudentController extends Controller
{
    /**
     * Get attendance history for a specific student.
     */
    public function attendance(Request $request, Student $student)
    {
        $this->authorizeParent($student);

        $limit = $request->get('limit', 30);
        
        $attendances = Attendance::where('student_id', $student->id)
            ->latest('attendance_time')
            ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $attendances
        ]);
    }

    public function subjectAttendance(Request $request, Student $student)
    {
        $this->authorizeParent($student);

        $limit = $request->get('limit', 30);

        $subjectAttendances = SubjectAttendance::with(['schedule.teachingAssignment.subject', 'schedule.teachingAssignment.teacher'])
            ->where('student_id', $student->id)
            ->latest('created_at')
            ->paginate($limit);

        $subjectAttendances->getCollection()->transform(function ($item) {
            if ($item->schedule && $item->schedule->teachingAssignment) {
                $item->schedule->subject = $item->schedule->teachingAssignment->subject;
                $item->schedule->teacher = $item->schedule->teachingAssignment->teacher;
            }
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => $subjectAttendances
        ]);
    }

    /**
     * Get schedule for a specific student.
     */
    public function schedule(Student $student)
    {
        $this->authorizeParent($student);

        if (!$student->school_class_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Siswa belum terdaftar di kelas manapun.'
            ], 404);
        }

        $schedules = Schedule::with(['teachingAssignment.subject', 'teachingAssignment.teacher'])
            ->whereHas('teachingAssignment', function ($query) use ($student) {
                $query->where('school_class_id', $student->school_class_id);
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(function ($item) {
                if ($item->teachingAssignment) {
                    $item->subject = $item->teachingAssignment->subject;
                    $item->teacher = $item->teachingAssignment->teacher;
                }
                return $item;
            });

        return response()->json([
            'status' => 'success',
            'data' => $schedules
        ]);
    }

    /**
     * Get teaching journals for the student's class.
     */
    public function journals(Request $request, Student $student)
    {
        $this->authorizeParent($student);

        $limit = $request->get('limit', 15);

        $journals = TeachingJournal::with(['schedule.teachingAssignment.subject', 'schedule.teachingAssignment.teacher'])
            ->whereHas('schedule.teachingAssignment', function($query) use ($student) {
                $query->where('school_class_id', $student->school_class_id);
            })
            ->latest('teaching_date')
            ->paginate($limit);

        $journals->getCollection()->transform(function ($item) {
            if ($item->schedule && $item->schedule->teachingAssignment) {
                $item->schedule->subject = $item->schedule->teachingAssignment->subject;
                $item->schedule->teacher = $item->schedule->teachingAssignment->teacher;
            }
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => $journals
        ]);
    }

    /**
     * Get teacher notes for the student.
     */
    public function notes(Student $student)
    {
        $this->authorizeParent($student);

        // TeacherNote in the current schema is for personal teacher notes, not student-specific.
        // Returning an empty array to prevent 500 errors.
        return response()->json([
            'status' => 'success',
            'data' => []
        ]);
    }

    /**
     * Helper to ensure the parent is authorized to view this student.
     */
    private function authorizeParent(Student $student)
    {
        $parent = Auth::user()->parent;
        
        if (!$parent || !$parent->students()->where('students.id', $student->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data siswa ini.');
        }
    }
}
