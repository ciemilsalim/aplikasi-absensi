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
        Schema::table('school_classes', function (Blueprint $table) {
            // Tambahkan kolom level_id setelah kolom 'name'
            // Kolom ini bisa null untuk data lama yang belum diatur
            $table->foreignId('level_id')->nullable()->constrained()->onDelete('set null')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            // Hapus foreign key constraint terlebih dahulu
            $table->dropForeign(['level_id']);
            // Hapus kolomnya
            $table->dropColumn('level_id');
        });
    }
};