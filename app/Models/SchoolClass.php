<?php
// File: app/Models/SchoolClass.php (Diperbarui)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod, SoftDeletes;
    
    // Asumsi Anda memiliki kolom 'level_id' di tabel 'school_classes'
    protected $fillable = ['name', 'teacher_id', 'level_id', 'semester_id', 'academic_year_id']; 

    public function students() 
    { 
        if (session()->has('active_semester_id')) {
            return $this->belongsToMany(Student::class, 'class_student')
                        ->wherePivot('semester_id', session('active_semester_id'));
        }
        return $this->hasMany(Student::class); 
    }

    // Relasi ke guru sebagai wali kelas
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
    
    // Relasi ke penugasan mengajar
    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /**
     * TAMBAHKAN FUNGSI INI
     * Mendefinisikan relasi ke model Level (Tingkat Kelas).
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
