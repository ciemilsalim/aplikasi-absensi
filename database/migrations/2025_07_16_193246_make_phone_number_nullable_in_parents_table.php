<?php
// File: database/migrations/YYYY_MM_DD_HHMMSS_make_phone_number_nullable_in_parents_table.php

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
        Schema::table('parents', function (Blueprint $table) {
            // Mengubah kolom phone_number agar bisa kosong (nullable)
            $table->string('phone_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            // Kembalikan ke kondisi semula jika rollback
            $table->string('phone_number')->nullable(false)->change();
        });
    }
};
