<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'attendance_time',
        'checkout_time',
        'status', // Ditambahkan untuk status keterlambatan
        'academic_year_id',
        'semester_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attendance_time' => 'datetime',
        'checkout_time'   => 'datetime',
    ];

    /**
     * Mendefinisikan relasi ke model Student.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
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
