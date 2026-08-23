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

use Illuminate\Support\Facades\Validator;

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
        Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ], [
            'name.required' => 'Harap isi Nama Lengkap Anda.',
            'name.string' => 'Nama Lengkap harus berupa teks.',
            'name.max' => 'Nama Lengkap tidak boleh lebih dari 255 karakter.',
            'email.required' => 'Harap isi Alamat Email Anda.',
            'email.email' => 'Format Alamat Email tidak valid (contoh: nama@email.com).',
            'email.max' => 'Alamat Email tidak boleh lebih dari 255 karakter.',
            'email.unique' => 'Alamat Email ini sudah terdaftar. Silakan gunakan email lain atau masuk di halaman login.',
            'password.required' => 'Harap isi Kata Sandi Anda.',
            'password.min' => 'Kata Sandi minimal harus terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi Kata Sandi tidak cocok dengan Kata Sandi yang dimasukkan.',
        ])->validate();

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
        try {
            ParentModel::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $request->name,
                    'is_onboarding_completed' => false,
                ]
            );
        } catch (\Throwable $e) {
            ParentModel::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $request->name]
            );
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
