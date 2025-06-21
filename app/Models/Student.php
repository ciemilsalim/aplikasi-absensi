<?php

// File: app/Models/Student.php
namespace App\Models;

use App\Models\ParentModel;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nis',
        'school_class_id',
        'unique_id'
    ];

    // Otomatis membuat unique_id saat siswa baru dibuat
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = (string) Str::uuid();
            }
        });
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Mendefinisikan relasi ke model ParentModel.
     */
    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id');
    }

    /**
     * Otomatis membuat unique_id saat siswa baru dibuat.
     * PERBAIKAN: Memastikan hanya ada satu metode boot() di dalam kelas.
     */
    // protected static function boot()
    // {
    //     parent::boot();
    //     static::creating(function ($model) {
    //         if (empty($model->unique_id)) {
    //             $model->unique_id = (string) Str::uuid();
    //         }
    //     });
    // }
}