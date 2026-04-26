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
            ->map(function ($student) use ($id, $request) {
                // Gunakan date dari request jika ada, jika tidak gunakan today
                $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
                
                // Cek apakah sudah diabsen pada tanggal tersebut untuk jadwal ini
                $attendance = SubjectAttendance::where('schedule_id', $id)
                    ->where('student_id', $student->id)
                    ->whereDate('created_at', $date)
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

        // Gunakan date dari request jika ada (untuk edit riwayat), jika tidak gunakan today
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $isPastDate = $date->isBefore(Carbon::today());

        // 1. Cek Akhir Pekan (Hanya jika input hari ini, untuk riwayat diizinkan edit)
        if (!$isPastDate && $date->isWeekend()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Absen mapel tidak dapat dilakukan pada akhir pekan.',
            ], 422);
        }

        // 2. Cek Hari Libur (Hanya jika input hari ini)
        if (!$isPastDate) {
            $holidays = \App\Models\Calendar::getHolidaysInRange($date, $date);
            if (\App\Models\Calendar::isDateInHolidays($date, $holidays)) {
                $holiday = $holidays->first(function($h) use ($date) {
                    $start = $h->start_date->copy()->startOfDay();
                    $end = $h->end_date ? $h->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
                    return $date->between($start, $end);
                });
                $title = $holiday ? $holiday->title : 'Hari Libur';
                return response()->json([
                    'status'  => 'error',
                    'message' => "Absen mapel dibatalkan: $title (Hari Libur).",
                ], 422);
            }
        }

        // 3. Cek Belajar Mandiri (Hanya jika input hari ini)
        if (!$isPastDate) {
            $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($date, $date);
            if (\App\Models\Calendar::isDateInSelfStudy($date, $selfStudyDays)) {
                $selfStudy = $selfStudyDays->first(function($h) use ($date) {
                    $start = $h->start_date->copy()->startOfDay();
                    $end = $h->end_date ? $h->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
                    return $date->between($start, $end);
                });
                $title = $selfStudy ? $selfStudy->title : 'Belajar Mandiri';
                return response()->json([
                    'status'  => 'error',
                    'message' => "Absen mapel dibatalkan: $title (Belajar Mandiri).",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($request->attendances as $att) {
                // Gunakan updateOrCreate dengan pencarian berdasarkan schedule_id, student_id, dan DATE(created_at)
                // Karena updateOrCreate bawaan Laravel tidak support whereDate, kita cari manual dulu
                $attendance = SubjectAttendance::where('schedule_id', $scheduleId)
                    ->where('student_id', $att['student_id'])
                    ->whereDate('created_at', $date)
                    ->first();

                if ($attendance) {
                    $attendance->update([
                        'teacher_id' => $teacherId,
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? null,
                    ]);
                } else {
                    SubjectAttendance::create([
                        'schedule_id' => $scheduleId,
                        'student_id' => $att['student_id'],
                        'teacher_id' => $teacherId,
                        'status' => $att['status'],
                        'notes' => $att['notes'] ?? null,
                        'created_at' => $date->startOfDay(), // Pastikan jamnya konsisten jika input manual masa lalu
                    ]);
                }
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

    /**
     * Get history of subject attendance sessions for the current teacher.
     * Grouped by date × schedule, with summary counts.
     */
    public function getHistory(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan guru.'], 403);
        }

        // Ambil semua schedule milik guru ini
        $scheduleIds = Schedule::whereHas('teachingAssignment', fn($q) => $q->where('teacher_id', $teacher->id))
            ->pluck('id');

        // Kelompokkan absensi berdasarkan schedule_id + tanggal
        $sessions = SubjectAttendance::whereIn('schedule_id', $scheduleIds)
            ->selectRaw('schedule_id, DATE(created_at) as date, 
                COUNT(*) as total,
                SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = "sakit" THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as alpa,
                SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) as bolos')
            ->groupBy('schedule_id', 'date')
            ->orderByDesc('date')
            ->get()
            ->map(function ($row) {
                $schedule = Schedule::with('teachingAssignment.subject:id,name', 'teachingAssignment.schoolClass:id,name')
                    ->find($row->schedule_id);
                return [
                    'schedule_id'  => $row->schedule_id,
                    'date'         => $row->date,
                    'subject_name' => $schedule->teachingAssignment->subject->name ?? '-',
                    'class_name'   => $schedule->teachingAssignment->schoolClass->name ?? '-',
                    'total'        => (int) $row->total,
                    'hadir'        => (int) $row->hadir,
                    'sakit'        => (int) $row->sakit,
                    'izin'         => (int) $row->izin,
                    'alpa'         => (int) $row->alpa,
                    'bolos'        => (int) $row->bolos,
                ];
            });

        return response()->json(['status' => 'success', 'data' => $sessions]);
    }

    /**
     * Get student detail for a specific history session (date + schedule_id).
     */
    public function getHistoryDetail(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'date'        => 'required|date',
        ]);

        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan guru.'], 403);
        }

        $date     = Carbon::parse($request->date)->toDateString();
        $schedId  = $request->schedule_id;

        $schedule = Schedule::with('teachingAssignment.subject:id,name', 'teachingAssignment.schoolClass:id,name')
            ->findOrFail($schedId);

        $records = SubjectAttendance::where('schedule_id', $schedId)
            ->whereDate('created_at', $date)
            ->with('student:id,name,nis')
            ->get()
            ->map(fn($r) => [
                'student_id'   => $r->student_id,
                'student_name' => $r->student->name ?? '-',
                'nis'          => $r->student->nis ?? '-',
                'status'       => $r->status,
                'notes'        => $r->notes,
            ]);

        return response()->json([
            'status'       => 'success',
            'date'         => $date,
            'subject_name' => $schedule->teachingAssignment->subject->name ?? '-',
            'class_name'   => $schedule->teachingAssignment->schoolClass->name ?? '-',
            'data'         => $records,
        ]);
    }

    /**
     * Get students needing attendance attention (high alpa/bolos count).
     * Threshold: >= 3 alpa/bolos across all subjects taught by this teacher.
     */
    public function getAttentionStudents(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Anda bukan guru.'], 403);
        }

        $threshold = (int) $request->query('threshold', 3);

        $scheduleIds = Schedule::whereHas('teachingAssignment', fn($q) => $q->where('teacher_id', $teacher->id))
            ->pluck('id');

        // Hitung kumulatif alpa+bolos per siswa per jadwal
        $rows = SubjectAttendance::whereIn('schedule_id', $scheduleIds)
            ->whereIn('status', ['alpa', 'bolos'])
            ->selectRaw('student_id, schedule_id,
                COUNT(*) as total_absent,
                SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as alpa,
                SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) as bolos')
            ->groupBy('student_id', 'schedule_id')
            ->having('total_absent', '>=', $threshold)
            ->orderByDesc('total_absent')
            ->get();

        // Enrich dengan nama siswa, mata pelajaran, kelas
        $result = $rows->map(function ($row) {
            $student  = Student::select('id', 'name', 'nis')->find($row->student_id);
            $schedule = Schedule::with('teachingAssignment.subject:id,name', 'teachingAssignment.schoolClass:id,name')
                ->find($row->schedule_id);
            return [
                'student_id'   => $row->student_id,
                'student_name' => $student->name ?? '-',
                'nis'          => $student->nis ?? '-',
                'subject_name' => $schedule->teachingAssignment->subject->name ?? '-',
                'class_name'   => $schedule->teachingAssignment->schoolClass->name ?? '-',
                'total_absent' => (int) $row->total_absent,
                'alpa'         => (int) $row->alpa,
                'bolos'        => (int) $row->bolos,
            ];
        });

        return response()->json(['status' => 'success', 'threshold' => $threshold, 'data' => $result]);
    }
}
