<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'teacher_id',
        'school_class_id',
        'subject_id',
        'academic_year_id',
        'semester_id',
        'date',
        'jp',
        'learning_objective',
        'topic',
        'activity',
        'assessment',
        'reflection',
        'follow_up',
        'students_achieved_count',
        'students_remedial_count',
        'students_enrichment_count',
        'attendance_hadir',
        'attendance_sakit',
        'attendance_izin',
        'attendance_alpa',
        'material_content',
        'notes',
        'is_verified',
        'verified_by',
        'verified_at',
        'supervisor_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'jp' => 'integer',
        'students_achieved_count' => 'integer',
        'students_remedial_count' => 'integer',
        'students_enrichment_count' => 'integer',
        'attendance_hadir' => 'integer',
        'attendance_sakit' => 'integer',
        'attendance_izin' => 'integer',
        'attendance_alpa' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope untuk memfilter berdasarkan guru
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope untuk memfilter berdasarkan kelas & mapel
     */
    public function scopeFilterClassSubject($query, $classId = null, $subjectId = null)
    {
        if ($classId) {
            $query->where('school_class_id', $classId);
        }
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        return $query;
    }
}
