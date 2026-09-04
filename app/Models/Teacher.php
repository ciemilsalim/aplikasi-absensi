<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'nip',
        'phone_number',
        'photo',
        'face_descriptor',
    ];

    // Relasi ke model User (satu guru memiliki satu akun login)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    // Relasi untuk mengecek apakah guru ini adalah wali kelas pada tahun ajaran aktif
    public function homeroomClass()
    {
        $activeYearId = session('active_academic_year_id') 
            ?? \App\Models\Semester::where('is_active', true)->value('academic_year_id');

        $relation = $this->hasOne(SchoolClass::class, 'teacher_id');

        if ($activeYearId) {
            $relation->where('school_classes.academic_year_id', $activeYearId);
        }

        return $relation;
    }

    // Seluruh riwayat kelas binaan wali kelas (lintas tahun ajaran)
    public function allHomeroomClasses()
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id')->withoutGlobalScope('academic_period');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher');
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function coachingExtracurriculars()
    {
        return $this->belongsToMany(Extracurricular::class, 'extracurricular_teacher');
    }

    /**
     * Relasi ke tim proyek kokurikuler yang diikuti oleh guru.
     */
    public function cocurriculars()
    {
        return $this->belongsToMany(Cocurricular::class, 'cocurricular_teacher');
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class);
    }

    public function semesterReflections()
    {
        return $this->hasMany(TeacherSemesterReflection::class);
    }

    public function studentAnecdotes()
    {
        return $this->hasMany(StudentAnecdote::class);
    }
}