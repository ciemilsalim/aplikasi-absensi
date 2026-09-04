<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Extracurricular extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod;

    protected $fillable = ['name', 'description', 'teacher_id', 'semester_id', 'academic_year_id'];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'extracurricular_teacher');
    }

    public function coach()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function students()
    {
        $relation = $this->belongsToMany(Student::class, 'extracurricular_student');
        $activeSemesterId = session('active_semester_id') ?? \App\Models\Semester::where('is_active', true)->value('id');
        $isCurrentActiveSemester = $activeSemesterId ? (bool)\App\Models\Semester::where('id', $activeSemesterId)->value('is_active') : true;
        if ($isCurrentActiveSemester) {
            $relation->where(function($q) {
                $q->where('students.status', 'aktif')
                  ->orWhereNull('students.status')
                  ->orWhere('students.status', '');
            })->whereNotIn('students.status', Student::$inactiveStatuses);
        }
        return $relation;
    }

    public function attendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class);
    }
}
