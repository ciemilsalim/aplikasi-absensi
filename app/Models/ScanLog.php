<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'student_id',
        'student_name',
        'student_nis',
        'scan_type',
        'failure_reason',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    /**
     * Relasi ke User yang melakukan scan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Student yang di-scan.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope untuk filter berdasarkan tipe scan.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('scan_type', $type);
    }

    /**
     * Scope untuk filter berdasarkan rentang tanggal.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('scanned_at', [$from, $to]);
    }
}
