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
        'religion',
        'unique_id',
        'photo',
        'face_descriptor',
        'status'
    ];

    protected $attributes = [
        'religion' => 'islam',
    ];

    public static $inactiveStatuses = [
        'tidak_aktif',
        'tidak aktif',
        'nonaktif',
        'non_aktif',
        'non aktif',
        'inactive',
        'mutasi',
        'keluar',
        'lulus',
        'pindah',
        'berhenti',
        'drop_out',
        'dropout',
        'alumni'
    ];

    /**
     * Scope untuk mengambil hanya siswa yang berstatus aktif.
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->where('students.status', 'aktif')
              ->orWhereNull('students.status')
              ->orWhere('students.status', '');
        })->whereNotIn('students.status', self::$inactiveStatuses);
    }

    /**
     * Cek apakah siswa berstatus aktif
     */
    public function isStudentActive(): bool
    {
        $st = strtolower(trim((string)$this->status));
        if (empty($st) || $st === 'aktif') {
            return !in_array($st, self::$inactiveStatuses);
        }
        return false;
    }

    protected $appends = ['photo_url'];

    /**
     * Accessor untuk mendapatkan URL lengkap foto siswa.
     * Mendukung foto yang diunggah dari Aplikasi Absensi maupun dari Portal Data SIPADA.
     * Kompatibel penuh dengan cPanel (subdomain terpisah, config:cache, dan penamaan berbasis NIS).
     */
    public function getPhotoUrlAttribute()
    {
        // Menggunakan config() agar aman dari php artisan config:cache di cPanel
        $sipadaUrl = rtrim(config('services.sipada.url', env('SIPADA_URL', 'http://localhost:8000')), '/');
        $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';

        // 1. Jika kolom photo di database terisi
        if (!empty($this->photo)) {
            if (Str::startsWith($this->photo, ['http://', 'https://'])) {
                return $this->photo;
            }

            // A. Cek file fisik di storage aplikasi-absensi (lokal / symlink)
            if (file_exists(public_path('storage/' . $this->photo))) {
                return asset('storage/' . $this->photo);
            }

            // B. Cek file fisik di direktori SIPADA (jika diletakkan di server/cPanel berdampingan)
            $sipadaStoragePath = env('SIPADA_STORAGE_DIR', base_path('../sistem-pangkalan-data/storage/app/public'));
            if (file_exists($sipadaStoragePath . '/' . $this->photo)) {
                return $sipadaUrl . '/storage/' . $this->photo;
            }

            // C. Fallback cPanel (subdomain terpisah): arahkan langsung ke domain SIPADA
            if (Str::startsWith($this->photo, ['student_photos/', 'students/photos/'])) {
                return $sipadaUrl . '/storage/' . $this->photo;
            }

            return asset('storage/' . $this->photo);
        }

        // 2. Jika kolom photo di database kosong, cek apakah foto SIPADA menggunakan nama NIS (contoh: student_photos/12345.jpg)
        if (!empty($this->nis)) {
            $extensions = ['jpg', 'jpeg', 'png', 'webp'];

            // Cek file fisik NIS di storage absensi
            foreach ($extensions as $ext) {
                $relativePath = 'student_photos/' . $this->nis . '.' . $ext;
                if (file_exists(public_path('storage/' . $relativePath))) {
                    return asset('storage/' . $relativePath);
                }
            }

            // Cek file fisik NIS di storage SIPADA
            $sipadaStoragePath = env('SIPADA_STORAGE_DIR', base_path('../sistem-pangkalan-data/storage/app/public'));
            foreach ($extensions as $ext) {
                $relativePath = 'student_photos/' . $this->nis . '.' . $ext;
                if (file_exists($sipadaStoragePath . '/' . $relativePath)) {
                    return $sipadaUrl . '/storage/' . $relativePath;
                }
            }

            // Fallback cPanel: Coba muat foto dari domain SIPADA berdasarkan NIS
            return $sipadaUrl . '/storage/student_photos/' . $this->nis . '.jpg';
        }

        return $defaultAvatar;
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

    public function anecdotes()
    {
        return $this->hasMany(StudentAnecdote::class);
    }
}
