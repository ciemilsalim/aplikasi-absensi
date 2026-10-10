<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAttendance extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod;

    protected $fillable = [
        'teacher_id',
        'status',
        'latitude',
        'longitude',
        'gps_accuracy',
        'is_mock_location',
        'device_info',
        'photo_evidence',
        'checkout_time',
        'checkout_photo_evidence',
        'checkout_latitude',
        'checkout_longitude',
        'checkout_gps_accuracy',
        'checkout_is_mock_location',
        'academic_year_id',
        'semester_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checkout_time' => 'datetime',
        'is_mock_location' => 'boolean',
        'checkout_is_mock_location' => 'boolean',
        'gps_accuracy' => 'float',
        'checkout_gps_accuracy' => 'float',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
