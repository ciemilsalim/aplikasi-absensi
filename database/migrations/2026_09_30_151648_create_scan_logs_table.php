<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel ini mencatat setiap aksi scan yang dilakukan oleh pengguna terdaftar.
     */
    public function up(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');          // Snapshot nama user saat scan
            $table->string('user_role');          // Snapshot role user saat scan
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('student_name')->nullable(); // Snapshot nama siswa
            $table->string('student_nis')->nullable();  // Snapshot NIS siswa
            $table->enum('scan_type', ['masuk', 'pulang', 'gagal'])->default('masuk');
            $table->string('failure_reason')->nullable(); // Alasan gagal jika scan_type=gagal
            $table->timestamp('scanned_at');      // Waktu scan dilakukan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
