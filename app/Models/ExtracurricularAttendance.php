<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtracurricularAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'academic_year_id',
        'semester_id',
        'status',
        'notes',
        'attendance_date'
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
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
