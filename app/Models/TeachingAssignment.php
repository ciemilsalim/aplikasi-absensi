<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingAssignment extends Model
{
    use HasFactory, \App\Traits\ScopedByAcademicPeriod;

    protected $fillable = ['school_class_id', 'subject_id', 'teacher_id', 'semester_id', 'academic_year_id'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Siswa yang terdaftar secara manual (override pivot) pada penugasan ini.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'teaching_assignment_student');
    }

    /**
     * Jadwal pelajaran yang terkait.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Mendapatkan daftar siswa yang diampu pada penugasan/mapel ini.
     * Logika Hybrid:
     * 1. Jika ada siswa yang dipilih eksplisit di pivot, gunakan daftar tersebut.
     * 2. Jika mapel adalah mapel agama, ambil siswa di kelas yang agamanya cocok.
     * 3. Default: ambil seluruh siswa di kelas.
     */
    public function getEnrolledStudents()
    {
        $activeSemesterId = session('active_semester_id') 
            ?? \App\Models\Semester::where('is_active', true)->value('id');
        $isCurrentActiveSemester = $activeSemesterId 
            ? (bool)\App\Models\Semester::where('id', $activeSemesterId)->value('is_active') 
            : true;

        // 1. Cek jika ada custom siswa di pivot
        $hasCustomStudents = $this->relationLoaded('students') 
            ? $this->students->isNotEmpty() 
            : $this->students()->exists();

        if ($hasCustomStudents) {
            $query = $this->relationLoaded('students') ? $this->students : $this->students();
            if ($isCurrentActiveSemester && !$this->relationLoaded('students')) {
                $query->where(function($q) {
                    $q->where('students.status', 'aktif')
                      ->orWhereNull('students.status')
                      ->orWhere('students.status', '');
                })->whereNotIn('students.status', Student::$inactiveStatuses);
            }
            $students = $this->relationLoaded('students') ? $query : $query->orderBy('name')->get();
            if ($isCurrentActiveSemester && $this->relationLoaded('students')) {
                $students = $students->filter(function($s) {
                    $st = strtolower(trim((string)($s->status ?? '')));
                    return (empty($st) || $st === 'aktif') && !in_array($st, Student::$inactiveStatuses);
                })->values();
            }
            return $students;
        }

        // 2. Ambil siswa dari kelas (mendukung multi-semester class_student & fallback)
        $schoolClass = $this->relationLoaded('schoolClass') ? $this->schoolClass : $this->schoolClass()->first();
        $classStudents = collect();
        if ($schoolClass) {
            $classStudents = $schoolClass->students()->orderBy('name')->get();
        }
        if ($classStudents->isEmpty() && $this->school_class_id) {
            $query = Student::where('school_class_id', $this->school_class_id);
            if ($isCurrentActiveSemester) {
                $query->where(function($q) {
                    $q->where('status', 'aktif')
                      ->orWhereNull('status')
                      ->orWhere('status', '');
                })->whereNotIn('status', Student::$inactiveStatuses);
            }
            $classStudents = $query->orderBy('name')->get();
        }

        // 3. Cek jika mapel agama
        $subject = $this->relationLoaded('subject') ? $this->subject : $this->subject()->first();
        if ($subject && $subject->category === 'religion' && !empty($subject->religion_key)) {
            $religionKey = strtolower(trim($subject->religion_key));
            return $classStudents->filter(function ($student) use ($religionKey) {
                return strtolower(trim((string)$student->religion)) === $religionKey;
            })->values();
        }

        // 4. Default: seluruh siswa di kelas
        return $classStudents;
    }
}