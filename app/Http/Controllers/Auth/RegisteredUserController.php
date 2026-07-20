<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ParentModel; // Impor model Parent
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 1. Buat user baru dengan peran 'parent'
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'parent', // PERBAIKAN: Menetapkan peran secara otomatis di aplikasi absensi
        ]);

        // 1.5. Pastikan terdaftar juga di tabel Spatie Roles milik SIPADA
        try {
            $role = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', 'parent')
                ->orWhere('name', 'Parent')
                ->first();
                
            if ($role) {
                \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $role->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ]);
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel tidak ada atau terjadi kesalahan integrasi
        }

        // 2. Buat juga data parent yang terhubung dengan user baru
        // Nomor HP bisa ditambahkan nanti oleh admin atau di halaman profil
        ParentModel::create([
            'user_id' => $user->id,
            'name' => $request->name,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
