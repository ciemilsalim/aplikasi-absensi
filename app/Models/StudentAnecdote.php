<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopedByAcademicPeriod;

class StudentAnecdote extends Model
{
    use HasFactory, ScopedByAcademicPeriod;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'schedule_id',
        'subject_id',
        'school_class_id',
        'academic_year_id',
        'semester_id',
        'date',
        'academic_note',
        'academic_sentiment',
        'attendance_note',
        'attendance_sentiment',
        'attitude_note',
        'attitude_sentiment',
        'follow_up',
        'is_visible_to_parents',
    ];

    protected $casts = [
        'date' => 'date',
        'is_visible_to_parents' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Scope filter berdasarkan guru
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope filter berdasarkan kelas
     */
    public function scopeForClass($query, $classId)
    {
        return $query->where('school_class_id', $classId);
    }

    /**
     * Helper untuk cek apakah memiliki catatan di salah satu kategori
     */
    public function hasAnyNote(): bool
    {
        return !empty($this->academic_note) || !empty($this->attendance_note) || !empty($this->attitude_note);
    }
}
