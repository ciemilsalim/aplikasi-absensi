<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectAttendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_id',
        'student_id',
        'teacher_id',
        'status',
        'notes',
        'academic_year_id',
        'semester_id',
    ];

    /**
     * Get the schedule associated with the attendance record.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Get the student associated with the attendance record.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the teacher who recorded the attendance.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
