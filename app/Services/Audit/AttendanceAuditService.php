<?php

namespace App\Services\Audit;

use App\Models\Attendance;
use App\Models\AttendanceAuditLog;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AttendanceAuditService
{
    /**
     * Mencatat entri audit log perubahan status presensi.
     */
    public function recordLog(
        Student $student,
        ?Attendance $attendance,
        ?string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?User $actor = null,
        string $actionType = 'manual_correction',
        ?string $oldNotes = null,
        ?string $newNotes = null,
        ?Carbon $attendanceDate = null
    ): AttendanceAuditLog {
        $student->loadMissing('schoolClass');

        $date = $attendanceDate
            ?? ($attendance && $attendance->attendance_time ? Carbon::parse($attendance->attendance_time) : now());

        $ip = request()?->ip();
        $ua = request()?->userAgent();

        return AttendanceAuditLog::create([
            'attendance_id'       => $attendance?->id,
            'student_id'          => $student->id,
            'student_name'        => $student->name,
            'student_nis'         => $student->nis,
            'class_name'          => $student->schoolClass?->name,
            'attendance_date'     => $date->toDateString(),
            'old_status'          => $oldStatus,
            'new_status'          => $newStatus,
            'old_notes'           => $oldNotes ?? $attendance?->notes,
            'new_notes'           => $newNotes,
            'reason'              => $reason ?? 'Koreksi presensi tanpa keterangan tambahan',
            'action_type'         => $actionType,
            'changed_by_user_id'  => $actor?->id,
            'changed_by_name'     => $actor?->name ?? 'Sistem',
            'changed_by_role'     => $actor?->role ?? 'system',
            'ip_address'          => $ip,
            'user_agent'          => $ua ? substr($ua, 0, 500) : null,
        ]);
    }

    /**
     * Membangun query daftar log audit dengan filter dinamis.
     */
    public function getAuditLogsQuery(array $filters = []): Builder
    {
        $query = AttendanceAuditLog::with(['student.schoolClass', 'changedByUser'])
            ->latest('id');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('student_nis', 'like', "%{$search}%")
                  ->orWhere('changed_by_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('attendance_date', [$filters['start_date'], $filters['end_date']]);
        } elseif (!empty($filters['start_date'])) {
            $query->where('attendance_date', '>=', $filters['start_date']);
        } elseif (!empty($filters['end_date'])) {
            $query->where('attendance_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['new_status'])) {
            $query->where('new_status', $filters['new_status']);
        }

        if (!empty($filters['school_class_id'])) {
            $query->whereHas('student', function ($q) use ($filters) {
                $q->where('school_class_id', $filters['school_class_id']);
            });
        }

        return $query;
    }

    /**
     * Mengambil ringkasan statistik koreksi presensi untuk dasbor / inspeksi.
     */
    public function getSummaryStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $baseQuery = AttendanceAuditLog::query();

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('attendance_date', [$startDate, $endDate]);
        }

        $totalCorrections = (clone $baseQuery)->count();
        $toPresentCount = (clone $baseQuery)->whereIn('new_status', ['tepat_waktu', 'terlambat'])->count();
        $toExcusedCount = (clone $baseQuery)->whereIn('new_status', ['izin', 'sakit'])->count();
        $toAbsentCount = (clone $baseQuery)->where('new_status', 'alpa')->count();
        $leaveSyncCount = (clone $baseQuery)->where('action_type', 'leave_sync')->count();

        return [
            'total_corrections' => $totalCorrections,
            'to_present_count'  => $toPresentCount,
            'to_excused_count'  => $toExcusedCount,
            'to_absent_count'   => $toAbsentCount,
            'leave_sync_count'  => $leaveSyncCount,
        ];
    }
}
