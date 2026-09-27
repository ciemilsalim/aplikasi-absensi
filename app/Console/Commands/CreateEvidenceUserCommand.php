<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class CreateEvidenceUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'siasek:create-evidence-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provisioning aman untuk akun evidence (siasek_evidence@example.com) dengan password tersembunyi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===================================================');
        $this->info(' PROVISIONING AKUN EVIDENCE (SIASEK VIEWER ROLE)  ');
        $this->info('===================================================');

        $email = 'siasek_evidence@example.com';
        $name = 'SIASEK Evidence';

        // 1. Password input tersembunyi (interaktif tanpa CLI argument)
        $password = $this->secret('Masukkan password aman untuk akun evidence (siasek_evidence@example.com)');
        if (empty($password) || strlen($password) < 8) {
            $this->error('❌ PERINGATAN: Password wajib diisi dan minimal 8 karakter!');
            return 1;
        }

        $passwordConfirm = $this->secret('Konfirmasi password');
        if ($password !== $passwordConfirm) {
            $this->error('❌ PERINGATAN: Konfirmasi password tidak cocok!');
            return 1;
        }

        // 2. Buat atau perbarui role 'viewer' di tabel roles
        $roleId = null;
        if (Schema::hasTable('roles')) {
            $role = DB::table('roles')->where('name', 'viewer')->first();
            if (!$role) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => 'viewer',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $roleId = $role->id;
            }
        }

        // 3. Cari user berdasarkan email utama atau identifier legacy untuk mencegah duplikasi
        $user = User::where('email', $email)
            ->orWhere('email', 'siasek_evidence@smpn1biau.sch.id')
            ->orWhere('name', 'siasek_evidence')
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'viewer',
                'email_verified_at' => now(),
            ]);
            $this->info("✔ User evidence dengan email '{$email}' berhasil dibuat.");
        } else {
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->role = 'viewer';
            $user->save();
            $this->info("✔ User evidence dengan email '{$email}' berhasil diperbarui.");
        }

        // 4. Hubungkan pivot model_has_roles
        if ($roleId && $user && Schema::hasTable('model_has_roles')) {
            $hasRolePivot = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->exists();

            if (!$hasRolePivot) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => get_class($user),
                    'model_id' => $user->id,
                ]);
            }
        }

        $this->info("✔ Akun evidence '{$email}' siap digunakan (ID: {$user->id}, Role: viewer).");
        return 0;
    }
}
