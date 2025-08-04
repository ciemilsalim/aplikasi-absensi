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
        'time_out',
        'time_in',
    ];

    protected $casts = [
        'time_out' => 'datetime',
        'time_in' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
