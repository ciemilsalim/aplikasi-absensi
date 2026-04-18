<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\SubjectAttendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Get the teaching schedule for the current logged-in teacher.
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        if (!$teacher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan guru.'
            ], 403);
        }

        // Ambil hari ini (1: Senin, ..., 7: Minggu)
        $today = Carbon::now()->dayOfWeekIso;

        $schedules = Schedule::whereHas('teachingAssignment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->with([
                'teachingAssignment.subject:id,name,code',
                'teachingAssignment.schoolClass:id,name'
            ])
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'subject_name' => $item->teachingAssignment->subject->name,
                    'subject_code' => $item->teachingAssignment->subject->code,
                    'class_name' => $item->teachingAssignment->schoolClass->name,
                    'start_time' => Carbon::parse($item->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($item->end_time)->format('H:i'),
                    'is_active' => $this->isScheduleActive($item->start_time, $item->end_time),
                ];
            });

        return response()->json([
            'status' => 'success',
            'today' => $today,
            'data' => $schedules
        ]);
    }

    /**
     * Get students list for a specific schedule/class.
     */
    public function getStudents(Request $request, $id)
    {
        $schedule = Schedule::with('teachingAssignment')->findOrFail($id);
        $classId = $schedule->teachingAssignment->school_class_id;

        $students = Student::where('school_class_id', $classId)
            ->select('id', 'name', 'nis', 'unique_id')
            ->get()
            ->map(function ($student) use ($id) {
                // Cek apakah sudah diabsen hari ini untuk jadwal ini
                $attendance = SubjectAttendance::where('schedule_id', $id)
                    ->where('student_id', $student->id)
                    ->whereDate('created_at', Carbon::today())
                    ->first();

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'unique_id' => $student->unique_id,
                    'status' => $attendance ? $attendance->status : 'belum_absen',
                    'notes' => $attendance ? $attendance->notes : '',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $students,
            'subject_name' => $schedule->teachingAssignment->subject->name,
            'class_name' => $schedule->teachingAssignment->schoolClass->name,
        ]);
    }

    /**
     * Store mass attendance for a subject session.
     */
    public function storeAttendance(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,sakit,izin,alpa,bolos',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $teacherId = $request->user()->teacher->id;
        $scheduleId = $request->schedule_id;

        DB::beginTransaction();
        try {
            foreach ($request->attendances as $att) {
                SubjectAttendance::updateOrCreate(
                    [
                        'schedule_id' => $scheduleId,
                        'student_id' => $att['student_id'],
                        'created_at' => Carbon::today()->startOfDay(), // Workaround for daily uniqueness if needed
                    ],
                    [
                        'teacher_id' => $teacherId,
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? null,
                    ]
                );
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Absensi mata pelajaran berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isScheduleActive($start, $end)
    {
        $now = Carbon::now();
        $startTime = Carbon::createFromFormat('H:i:s', $start);
        $endTime = Carbon::createFromFormat('H:i:s', $end);
        
        return $now->between($startTime, $endTime);
    }
}
