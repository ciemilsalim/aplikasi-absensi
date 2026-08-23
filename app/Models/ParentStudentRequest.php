<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentStudentRequest extends Model
{
    use HasFactory;

    protected $table = 'parent_student_requests';

    protected $fillable = [
        'parent_id',
        'student_id',
        'relationship',
        'verification_code',
        'status',
        'notes',
        'verified_by_user_id',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
