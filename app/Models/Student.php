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

    protected $appends = ['photo_url'];

    /**
     * Accessor untuk mendapatkan URL lengkap foto siswa.
     * Mendukung foto yang diunggah dari Aplikasi Absensi maupun dari Portal Data SIPADA.
     * Kompatibel penuh dengan cPanel (subdomain terpisah maupun folder berdampingan).
     */
    public function getPhotoUrlAttribute()
    {
        if (empty($this->photo)) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
        }

        if (Str::startsWith($this->photo, ['http://', 'https://'])) {
            return $this->photo;
        }

        // 1. Cek jika file fisik ada di storage publik aplikasi-absensi (lokal / symlink)
        if (file_exists(public_path('storage/' . $this->photo))) {
            return asset('storage/' . $this->photo);
        }

        // 2. Cek jika file fisik ada di direktori SIPADA di server/cPanel (opsional via env SIPADA_STORAGE_DIR)
        $sipadaStoragePath = env('SIPADA_STORAGE_DIR', base_path('../sistem-pangkalan-data/storage/app/public'));
        if (file_exists($sipadaStoragePath . '/' . $this->photo)) {
            $sipadaUrl = rtrim(env('SIPADA_URL', 'http://localhost:8000'), '/');
            return $sipadaUrl . '/storage/' . $this->photo;
        }

        // 3. Fallback cPanel: Untuk foto yang diunggah via SIPADA ('student_photos/...'), arahkan ke URL domain SIPADA
        if (Str::startsWith($this->photo, 'student_photos/')) {
            $sipadaUrl = rtrim(env('SIPADA_URL', 'http://localhost:8000'), '/');
            return $sipadaUrl . '/storage/' . $this->photo;
        }

        return asset('storage/' . $this->photo);
    }

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
