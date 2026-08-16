<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cocurricular extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'level_id',
        'code',
        'title',
        'activity_type',
        'dimensions',
        'time_allocation',
        'learning_objectives',
    ];

    protected $casts = [
        'dimensions' => 'array',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'cocurricular_teacher');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'cocurricular_id');
    }
}
