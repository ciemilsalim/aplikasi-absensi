<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod;

    protected $fillable = [
        'teaching_assignment_id',
        'cocurricular_id',
        'schedule_type',
        'day_of_week',
        'start_time',
        'end_time',
        'teacher_id',
        'school_class_id',
        'semester_id',
        'academic_year_id',
    ];

    /**
     * Mendapatkan data penugasan (guru, mapel, kelas)
     * yang terkait dengan jadwal ini (untuk jadwal tipe regular).
     */
    public function teachingAssignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    /**
     * Relasi untuk absensi per mata pelajaran atau kokurikuler.
     */
    public function subjectAttendances()
    {
        return $this->hasMany(SubjectAttendance::class);
    }

    /**
     * Relasi untuk jurnal mengajar pada sesi jadwal ini.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class);
    }

    /**
     * Relasi untuk catatan anekdot siswa pada sesi jadwal ini.
     */
    public function anecdotes()
    {
        return $this->hasMany(StudentAnecdote::class);
    }

    /**
     * Mendapatkan data proyek kokurikuler yang terkait dengan jadwal ini.
     */
    public function cocurricular()
    {
        return $this->belongsTo(Cocurricular::class);
    }

    /**
     * Mendapatkan data kelas untuk jadwal (terutama jadwal kokurikuler).
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Mendapatkan data guru pengampu untuk jadwal (terutama jadwal kokurikuler).
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Helper untuk memeriksa apakah jadwal merupakan kokurikuler.
     */
    public function isCocurricular(): bool
    {
        return $this->schedule_type === 'cocurricular';
    }

    /**
     * Helper untuk mendapatkan objek kelas terlepas dari tipe jadwal.
     */
    public function getTargetClass()
    {
        if ($this->isCocurricular()) {
            return $this->schoolClass;
        }
        return $this->teachingAssignment?->schoolClass;
    }

    /**
     * Helper untuk mendapatkan nama mata pelajaran / proyek.
     */
    public function getActivityName(): string
    {
        if ($this->isCocurricular()) {
            return $this->cocurricular?->title ?? 'Proyek Kokurikuler';
        }
        return $this->teachingAssignment?->subject?->name ?? 'Mata Pelajaran';
    }

    /**
     * Helper untuk mendapatkan guru utama pengampu jadwal.
     */
    public function getAssignedTeacher()
    {
        if ($this->isCocurricular()) {
            return $this->teacher;
        }
        return $this->teachingAssignment?->teacher;
    }

    /**
     * Helper untuk mendapatkan nama hari (Senin - Minggu).
     */
    public function getDayName(): string
    {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return $dayNames[$this->day_of_week] ?? (is_numeric($this->day_of_week) ? 'Hari ' . $this->day_of_week : (string)$this->day_of_week);
    }

    /**
     * Mendapatkan daftar siswa yang mengikuti jadwal ini.
     */
    public function getEnrolledStudents()
    {
        if ($this->teachingAssignment) {
            return $this->teachingAssignment->getEnrolledStudents();
        }
        $schoolClass = $this->relationLoaded('schoolClass') ? $this->schoolClass : $this->schoolClass()->first();
        if ($schoolClass) {
            $classStudents = $schoolClass->students()->orderBy('name')->get();
            if ($classStudents->isNotEmpty()) {
                return $classStudents;
            }
        }
        if ($this->school_class_id) {
            return Student::where('school_class_id', $this->school_class_id)->orderBy('name')->get();
        }
        return collect();
    }
}
