<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke orang tua. Jika data orang tua dihapus, percakapan ini juga akan terhapus.
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade');
            // Menghubungkan ke guru. Jika data guru dihapus, percakapan ini juga akan terhapus.
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            // Menghubungkan ke siswa yang menjadi topik percakapan.
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('conversations'); }
};
