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

    /**
     * Ambil nilai konfigurasi berdasarkan key dengan nilai fallback default.
     * Mengembalikan default jika key tidak ditemukan atau bernilai null.
     */
    public static function valueFor(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }
}
