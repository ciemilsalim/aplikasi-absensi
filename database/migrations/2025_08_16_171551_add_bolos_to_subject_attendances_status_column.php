<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subject_attendances', function (Blueprint $table) {
            // Mengubah kolom enum untuk menambahkan 'bolos'
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa', 'bolos'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_attendances', function (Blueprint $table) {
            // Mengembalikan ke kondisi semula jika migrasi di-rollback
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa'])->change();
        });
    }
};