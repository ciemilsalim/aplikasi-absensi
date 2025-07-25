<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // TAMBAHKAN BARIS INI
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_seen_at' => 'datetime', // Tambahkan baris ini
    ];

    /**
     * Mendefinisikan relasi "satu-ke-satu" ke model ParentModel.
     * INI ADALAH PERBAIKAN UNTUK ERROR ANDA.
     */
    public function parent()
    {
        return $this->hasOne(ParentModel::class);
    }

        public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    // File: app/Models/User.php (Diperbarui)
// Tambahkan metode ini ke model User Anda
    public function notifications() {
        return $this->hasMany(Notification::class);
    }
}
