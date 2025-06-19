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
        Schema::table('attendances', function (Blueprint $table) {
            // Menambahkan kolom jam pulang setelah jam masuk (attendance_time)
            // Dibuat nullable karena akan kosong saat siswa baru absen masuk
            $table->timestamp('checkout_time')->nullable()->after('attendance_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('checkout_time');
        });
    }
};
