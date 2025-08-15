<?php
// File: app/Models/SchoolClass.php (Diperbarui)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'teacher_id']; // Tambahkan teacher_id

    public function students() { return $this->hasMany(Student::class); }

    // Relasi baru ke guru sebagai wali kelas
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}