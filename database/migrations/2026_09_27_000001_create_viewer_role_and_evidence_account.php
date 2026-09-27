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
        // Migration HANYA bertanggung jawab memastikan role 'viewer' tersedia di tabel roles
        if (Schema::hasTable('roles')) {
            $roleExists = DB::table('roles')->where('name', 'viewer')->exists();
            if (!$roleExists) {
                DB::table('roles')->insert([
                    'name' => 'viewer',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback hanya membatalkan role 'viewer' jika tidak sedang terikat pada user manapun
        if (Schema::hasTable('roles')) {
            $role = DB::table('roles')->where('name', 'viewer')->first();
            if ($role) {
                $isUsedInUsers = DB::table('users')->where('role', 'viewer')->exists();
                $isUsedInPivot = Schema::hasTable('model_has_roles') && DB::table('model_has_roles')->where('role_id', $role->id)->exists();

                if (!$isUsedInUsers && !$isUsedInPivot) {
                    DB::table('roles')->where('id', $role->id)->delete();
                }
            }
        }
    }
};
