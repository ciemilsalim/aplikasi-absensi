<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'student_id',
        'student_name',
        'student_nis',
        'class_name',
        'attendance_date',
        'old_status',
        'new_status',
        'old_notes',
        'new_notes',
        'reason',
        'action_type',
        'changed_by_user_id',
        'changed_by_name',
        'changed_by_role',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Scope filter berdasarkan rentang tanggal kehadiran.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('attendance_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            return $query->where('attendance_date', '>=', $startDate);
        } elseif ($endDate) {
            return $query->where('attendance_date', '<=', $endDate);
        }
        return $query;
    }
}
