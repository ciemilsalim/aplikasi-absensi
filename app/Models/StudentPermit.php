<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPermit extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'attendance_id',
        'reason',
        'permit_type',
        'pass_code',
        'approved_by_user_id',
        'approved_by_name',
        'security_user_id',
        'security_name',
        'status',
        'time_out',
        'time_in',
        'checkout_time',
        'checkin_time',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'time_out'    => 'datetime',
        'time_in'     => 'datetime',
        'valid_until' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function getCheckoutTimeAttribute()
    {
        return $this->time_out;
    }

    public function setCheckoutTimeAttribute($value)
    {
        $this->attributes['time_out'] = $value;
    }

    public function getCheckinTimeAttribute()
    {
        return $this->time_in;
    }

    public function setCheckinTimeAttribute($value)
    {
        $this->attributes['time_in'] = $value;
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function security()
    {
        return $this->belongsTo(User::class, 'security_user_id');
    }

    /**
     * Booted model events.
     */
    protected static function booted()
    {
        static::creating(function ($permit) {
            if (empty($permit->pass_code)) {
                $permit->pass_code = static::generateUniquePassCode();
            }
        });
    }

    /**
     * Generate unique random pass code for gate scanner.
     */
    public static function generateUniquePassCode(): string
    {
        do {
            $code = 'PASS-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('pass_code', $code)->exists());

        return $code;
    }

    /**
     * Memastikan permit memiliki pass code yang valid (backfill otomatis untuk data historis).
     */
    public function ensurePassCode(): string
    {
        if (empty($this->pass_code)) {
            $this->pass_code = static::generateUniquePassCode();
            $this->saveQuietly();
        }

        return $this->pass_code;
    }

    /**
     * Label jenis izin berbahasa Indonesia.
     */
    public function getPermitTypeLabelAttribute(): string
    {
        return match ($this->permit_type) {
            'pulang_cepat'      => 'Izin Pulang Cepat',
            'keluar_sementara'  => 'Izin Keluar Sementara',
            'dispensasi_lomba'  => 'Dispensasi Kegiatan / Lomba',
            'tugas_sekolah', 'tugas_luar' => 'Tugas Luar Sekolah',
            default             => 'Izin Khusus',
        };
    }
}
