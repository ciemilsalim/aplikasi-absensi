<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Menggunakan Schema::table untuk memodifikasi tabel 'users' yang sudah ada
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom 'role' dengan tipe string.
            // Kolom ini akan ditempatkan setelah kolom 'password' untuk kerapian.
            // Nilai default 'user' diberikan agar semua pengguna yang sudah ada
            // secara otomatis mendapatkan peran ini dan tidak menyebabkan error.
            $table->string('role')->default('user')->after('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Jika migrasi di-rollback, kolom 'role' akan dihapus.
            $table->dropColumn('role');
        });
    }
};
