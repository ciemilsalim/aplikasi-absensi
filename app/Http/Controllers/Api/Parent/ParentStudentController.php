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

    /**
     * Get subject attendance history for a specific student.
     */
    public function subjectAttendance(Request $request, Student $student)
    {
        $this->authorizeParent($student);

        $limit = $request->get('limit', 30);

        $subjectAttendances = SubjectAttendance::with(['schedule.subject', 'schedule.teacher'])
            ->where('student_id', $student->id)
            ->latest('created_at')
            ->paginate($limit);

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

        $schedules = Schedule::with(['subject', 'teacher'])
            ->where('school_class_id', $student->school_class_id)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

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

        $journals = TeachingJournal::with(['schedule.subject', 'schedule.teacher'])
            ->whereHas('schedule', function($query) use ($student) {
                $query->where('school_class_id', $student->school_class_id);
            })
            ->latest('teaching_date')
            ->paginate($limit);

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

        $notes = TeacherNote::with('teacher')
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notes
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
