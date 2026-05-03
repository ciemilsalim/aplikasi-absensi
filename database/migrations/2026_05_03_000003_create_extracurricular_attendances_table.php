<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurricular_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('semester_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa']);
            $table->text('notes')->nullable();
            $table->date('attendance_date');
            $table->timestamps();

            $table->unique(['extracurricular_id', 'student_id', 'attendance_date'], 'extracurricular_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_attendances');
    }
};
