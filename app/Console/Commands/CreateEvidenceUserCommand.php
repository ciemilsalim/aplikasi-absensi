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
    protected $description = 'Provisioning terisolasi dan interaktif untuk akun evidence (siasek_evidence@example.com)';

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

        // 1. Cek konflik akun legacy lain secara informatif (TANPA merge/delete otomatis)
        $conflicts = User::where(function ($q) use ($email) {
            $q->where('name', 'siasek_evidence')
              ->orWhere('email', 'siasek_evidence@smpn1biau.sch.id');
        })->where('email', '!=', $email)->get();

        if ($conflicts->count() > 0) {
            $this->warn('⚠ DETEKSI KONFLIK AKUN LEGACY:');
            foreach ($conflicts as $c) {
                $this->warn("   - ID: {$c->id} | Name: {$c->name} | Email: {$c->email} | Role: {$c->role}");
            }
            $this->warn('   Sistem TIDAK melakukan merge atau penghapusan otomatis. Akun legacy di atas tetap aman.');
            $this->newLine();
        }

        // 2. Interactive secret input untuk password
        $password = $this->secret('Masukkan password aman untuk akun evidence (siasek_evidence@example.com)');
        if (empty($password) || strlen($password) < 8) {
            $this->error('❌ ERROR: Password wajib diisi dan minimal 8 karakter!');
            return 1;
        }

        $passwordConfirm = $this->secret('Konfirmasi password');
        if ($password !== $passwordConfirm) {
            $this->error('❌ ERROR: Konfirmasi password tidak cocok!');
            return 1;
        }

        // 3. Pastikan role 'viewer' ada di tabel roles
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

        // 4. Cari atau buat user berdasarkan email utama siasek_evidence@example.com
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'viewer',
                'email_verified_at' => now(),
            ]);
            $this->info("✔ Akun evidence baru dengan email '{$email}' berhasil dibuat.");
        } else {
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->role = 'viewer';
            $user->save();
            $this->info("✔ Akun evidence '{$email}' (ID: {$user->id}) berhasil diperbarui.");
        }

        // 5. Hubungkan pivot model_has_roles
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

        $this->info("✔ Provisioning selesai! User ID: {$user->id}, Email: {$email}, Role: viewer.");
        return 0;
    }
}
