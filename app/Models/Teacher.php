<?php
// File: app/Models/Teacher.php (Diperbarui)
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'nip',
        'phone_number',
    ];

    // Relasi ke model User (satu guru memiliki satu akun login)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi untuk mengecek apakah guru ini adalah wali kelas
    public function homeroomClass()
    {
        return $this->hasOne(SchoolClass::class, 'teacher_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher');
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}