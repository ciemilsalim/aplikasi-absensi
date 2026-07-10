<?php

// File: app/Models/Student.php
namespace App\Models;

use App\Models\User;
use App\Models\ParentModel;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'nis',
        'school_class_id',
        'unique_id',
        'photo',
        'face_descriptor'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Otomatis membuat unique_id saat siswa baru dibuat
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = (string)Str::uuid();
            }

            // Otomatis buat user account jika belum ada
            if (empty($model->user_id)) {
                $user = User::create([
                    'name' => $model->name,
                    'email' => $model->nis . '@mokopani.com',
                    'password' => bcrypt($model->nis),
                    'role' => 'student',
                ]);
                $model->user_id = $user->id;
            }
        });
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi baru untuk absensi per mata pelajaran.
     * Seorang siswa dapat memiliki banyak catatan absensi mata pelajaran.
     */
    public function subjectAttendances()
    {
        return $this->hasMany(SubjectAttendance::class);
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
        return $this->belongsToMany(ParentModel::class , 'parent_student', 'student_id', 'parent_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function extracurriculars()
    {
        return $this->belongsToMany(Extracurricular::class, 'extracurricular_student');
    }

    public function extracurricularAttendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class);
    }
}
