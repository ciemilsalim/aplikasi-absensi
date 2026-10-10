<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Audit\AttendanceAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceAuditLogController extends Controller
{
    public function __construct(
        protected AttendanceAuditService $auditService
    ) {}

    /**
     * Menampilkan daftar riwayat koreksi dan jejak audit presensi.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'start_date',
            'end_date',
            'action_type',
            'new_status',
            'school_class_id',
        ]);

        $query = $this->auditService->getAuditLogsQuery($filters);
        $logs = $query->paginate(20)->withQueryString();

        $statistics = $this->auditService->getSummaryStatistics(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.audit-logs.index', compact('logs', 'statistics', 'classes', 'filters'));
    }

    /**
     * Memproses koreksi status kehadiran manual oleh Admin/TU dengan pencatatan audit.
     */
    public function correct(Request $request)
    {
        $validated = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'attendance_date' => 'required|date',
            'new_status'      => 'required|in:tepat_waktu,terlambat,izin,sakit,alpa',
            'reason'          => 'required|string|min:4|max:500',
            'notes'           => 'nullable|string|max:255',
        ]);

        $student = Student::with('schoolClass')->findOrFail($validated['student_id']);
        $date = Carbon::parse($validated['attendance_date'])->startOfDay();

        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('attendance_time', $date)
            ->first();

        $oldStatus = $attendance ? $attendance->status : 'belum_absen';
        $oldNotes = $attendance ? $attendance->notes : null;

        if ($attendance) {
            $attendance->status = $validated['new_status'];
            if (!empty($validated['notes'])) {
                $attendance->notes = $validated['notes'];
            }
            if (in_array($validated['new_status'], ['izin', 'sakit', 'alpa'])) {
                $attendance->checkout_time = null;
            }
            $attendance->save();
        } else {
            $attendance = Attendance::create([
                'student_id'      => $student->id,
                'attendance_time' => $date->copy()->setTime(7, 15, 0),
                'status'          => $validated['new_status'],
                'notes'           => $validated['notes'] ?? null,
            ]);
        }

        // Catat ke log audit secara permanen
        $this->auditService->recordLog(
            student: $student,
            attendance: $attendance,
            oldStatus: $oldStatus,
            newStatus: $validated['new_status'],
            reason: $validated['reason'],
            actor: $request->user(),
            actionType: 'manual_correction',
            oldNotes: $oldNotes,
            newNotes: $validated['notes'] ?? null,
            attendanceDate: $date
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Presensi {$student->name} berhasil dikoreksi menjadi '{$validated['new_status']}'.",
                'data'    => [
                    'student_name' => $student->name,
                    'new_status'   => $validated['new_status'],
                    'date'         => $date->toDateString(),
                ],
            ]);
        }

        return redirect()->back()->with('success', "Presensi {$student->name} tanggal {$date->format('d/m/Y')} berhasil dikoreksi menjadi '{$validated['new_status']}'. Riwayat perubahan telah dicatat di Audit Trail.");
    }
}
