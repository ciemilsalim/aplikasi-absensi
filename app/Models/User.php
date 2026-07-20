<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    // File: app/Models/User.php (Diperbarui)
// Tambahkan metode ini ke model User Anda
    public function notifications() {
        return $this->hasMany(Notification::class);
    }

    /**
     * Memeriksa apakah pengguna memiliki salah satu dari peran yang diberikan (case-insensitive).
     * Mendukung pembacaan dari tabel Spatie Permission (SIPADA) dan kolom role lokal.
     */
    public function hasAnyRole(array $roles): bool
    {
        $normalizedRoles = array_map('strtolower', $roles);

        // 1. Coba cek dari tabel Spatie Permission (model_has_roles & roles) yang digunakan SIPADA
        try {
            $hasSpatieRole = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $this->id)
                ->where('model_has_roles.model_type', get_class($this))
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(roles.name)'), $normalizedRoles)
                ->exists();

            if ($hasSpatieRole) {
                return true;
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel Spatie belum dimigrasi atau terjadi error database
        }

        // 2. Fallback ke kolom role lokal di tabel users
        return in_array(strtolower($this->role), $normalizedRoles);
    }

    /**
     * Memeriksa apakah pengguna memiliki peran tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->hasAnyRole([$role]);
    }
}
