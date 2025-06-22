<?php
// File: app/Models/Teacher.php (Baru)

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}