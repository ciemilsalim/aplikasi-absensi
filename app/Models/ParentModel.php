<?php
// File: app/Models/ParentModel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    use HasFactory;
    protected $table = 'parents';
    protected $fillable = ['user_id', 'name', 'phone_number'];

    // Relasi ke model User (satu orang tua memiliki satu akun login)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke model Student (satu orang tua bisa memiliki banyak anak)
    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id');
    }
    
    // Relasi ke model LeaveRequest
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function adminConversation()
    {
        // Asumsi admin_id adalah 1, atau Anda bisa mengambilnya dari Auth::id() jika konteksnya memungkinkan.
        // Namun, untuk relasi murni, lebih baik definisikan seperti ini.
        return $this->hasOne(AdminConversation::class, 'parent_id');
    }
}