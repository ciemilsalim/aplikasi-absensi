<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }
}
