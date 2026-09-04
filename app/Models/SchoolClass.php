<?php
// File: app/Models/SchoolClass.php (Diperbarui)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod, SoftDeletes;
    
    protected $fillable = ['name', 'teacher_id', 'level_id', 'semester_id', 'academic_year_id']; 

    /**
     * Override scope agar kelas pada tahun ajaran yang dipilih otomatis aktif
     * di seluruh semester dalam tahun ajaran tersebut (Ganjil & Genap)
     */
    protected static function bootScopedByAcademicPeriod()
    {
        static::addGlobalScope('academic_period', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (session()->has('active_academic_year_id')) {
                $builder->where('school_classes.academic_year_id', session('active_academic_year_id'));
            } elseif (session()->has('active_semester_id')) {
                $builder->where(function ($q) {
                    $q->where('school_classes.semester_id', session('active_semester_id'))
                      ->orWhere('school_classes.academic_year_id', session('active_academic_year_id'));
                });
            }
        });

        static::creating(function ($model) {
            if (session()->has('active_semester_id') && empty($model->semester_id)) {
                $model->semester_id = session('active_semester_id');
                $model->academic_year_id = session('active_academic_year_id');
            }
        });
    }

    public function students() 
    { 
        $activeSemesterId = session('active_semester_id') 
            ?? \App\Models\Semester::where('is_active', true)->value('id');

        if ($activeSemesterId && \Illuminate\Support\Facades\Schema::hasTable('class_student')) {
            return $this->belongsToMany(Student::class, 'class_student')
                        ->wherePivot('semester_id', $activeSemesterId);
        }
        return $this->hasMany(Student::class, 'school_class_id'); 
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
