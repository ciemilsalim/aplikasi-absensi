<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'status',
        'latitude',
        'longitude',
        'photo_evidence',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
