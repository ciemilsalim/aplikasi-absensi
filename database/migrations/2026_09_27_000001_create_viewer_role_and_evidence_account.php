<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $username = 'siasek_evidence';
        $email = 'siasek_evidence@smpn1biau.sch.id';
        $password = env('SIASEK_EVIDENCE_PASSWORD', 'qwerty123');

        // 1. Pastikan role 'viewer' ada di tabel roles
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

        // 2. Pastikan user 'siasek_evidence' ada
        $user = DB::table('users')->where('name', $username)->orWhere('email', $email)->first();
        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'name' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'viewer',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $userId = $user->id;
            DB::table('users')->where('id', $userId)->update([
                'role' => 'viewer',
                'updated_at' => now(),
            ]);
        }

        // 3. Hubungkan pivot model_has_roles
        if ($roleId && $userId && Schema::hasTable('model_has_roles')) {
            $hasRolePivot = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->exists();

            if (!$hasRolePivot) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $user = DB::table('users')->where('name', 'siasek_evidence')->first();
        if ($user) {
            if (Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')->where('model_id', $user->id)->delete();
            }
            DB::table('users')->where('id', $user->id)->delete();
        }
    }
};
