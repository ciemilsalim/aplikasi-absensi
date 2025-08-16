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
        Schema::create('subject_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa']);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Menambahkan unique constraint untuk memastikan satu siswa hanya bisa diabsen sekali
            // untuk satu jadwal pelajaran pada hari yang sama.
            $table->unique(['schedule_id', 'student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_attendances');
    }
};
