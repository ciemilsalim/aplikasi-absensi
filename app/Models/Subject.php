<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'category', 'religion_key'];

    public function isReligion(): bool
    {
        return $this->category === 'religion';
    }

    /**
     * Guru yang mengajar mata pelajaran ini.
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'subject_teacher');
    }
}