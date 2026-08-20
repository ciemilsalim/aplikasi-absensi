<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update user role di tabel users
        $marlinda = DB::table('users')->where('name', 'LIKE', '%Marlinda%')->first();
        if ($marlinda) {
            DB::table('users')->where('id', $marlinda->id)->update([
                'role' => 'kepala_sekolah',
                'updated_at' => now(),
            ]);

            // 2. Jika tabel Spatie roles & model_has_roles ada (digunakan SIPADA / SSO)
            if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
                try {
                    $role = DB::table('roles')->where('name', 'kepala_sekolah')->first();
                    if (!$role) {
                        $roleId = DB::table('roles')->insertGetId([
                            'name' => 'kepala_sekolah',
                            'guard_name' => 'web',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $roleId = $role->id;
                    }

                    // Hapus role operator lama jika ada
                    $operatorRole = DB::table('roles')->where('name', 'operator')->first();
                    if ($operatorRole) {
                        DB::table('model_has_roles')
                            ->where('model_id', $marlinda->id)
                            ->where('role_id', $operatorRole->id)
                            ->delete();
                    }

                    // Tambahkan role kepala_sekolah
                    $hasRole = DB::table('model_has_roles')
                        ->where('model_id', $marlinda->id)
                        ->where('role_id', $roleId)
                        ->exists();

                    if (!$hasRole) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $roleId,
                            'model_type' => 'App\\Models\\User',
                            'model_id' => $marlinda->id,
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Abaikan jika ada kendala tabel Spatie
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $marlinda = DB::table('users')->where('name', 'LIKE', '%Marlinda%')->first();
        if ($marlinda) {
            DB::table('users')->where('id', $marlinda->id)->update([
                'role' => 'operator',
                'updated_at' => now(),
            ]);
        }
    }
};
