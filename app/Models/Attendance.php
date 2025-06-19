<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'attendance_time',
        'checkout_time', // Ditambahkan untuk mass assignment
    ];

    /**
     * The attributes that should be cast.
     * INI ADALAH PERBAIKAN UNTUK ERROR ANDA.
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
}
