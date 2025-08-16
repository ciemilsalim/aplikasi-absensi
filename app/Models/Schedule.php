<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'teaching_assignment_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    /**
     * Mendapatkan data penugasan (guru, mapel, kelas)
     * yang terkait dengan jadwal ini.
     */
    public function teachingAssignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    /**
     * Relasi baru untuk absensi per mata pelajaran.
     * Satu jadwal pelajaran dapat memiliki banyak catatan absensi siswa.
     */
    public function subjectAttendances()
    {
        return $this->hasMany(SubjectAttendance::class);
    }
}
