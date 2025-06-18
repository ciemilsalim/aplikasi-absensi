<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'attendance_time'
    ];

    protected $casts = [
        'attendance_time' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
