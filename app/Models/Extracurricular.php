<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Extracurricular extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod;

    protected $fillable = ['name', 'description', 'teacher_id', 'semester_id', 'academic_year_id'];

    public function coach()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'extracurricular_student');
    }

    public function attendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class);
    }
}
