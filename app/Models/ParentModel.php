<?php
// File: app/Models/ParentModel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Nama kelas diubah menjadi ParentModel untuk menghindari konflik
class ParentModel extends Model
{
    use HasFactory;

    // Nama tabel secara eksplisit didefinisikan sebagai 'parents'
    protected $table = 'parents';

    protected $fillable = ['user_id', 'name', 'phone_number'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id');
    }
}
