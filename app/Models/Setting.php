<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];
    
    // Nonaktifkan timestamps (created_at, updated_at) untuk tabel ini
    public $timestamps = false; 
}
