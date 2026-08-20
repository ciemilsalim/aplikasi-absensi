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
        Schema::create('student_anecdotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('set null');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->date('date');
            
            // Catatan Akademik
            $table->text('academic_note')->nullable();
            $table->enum('academic_sentiment', ['positive', 'neutral', 'needs_guidance'])->default('neutral');
            
            // Catatan Kehadiran
            $table->text('attendance_note')->nullable();
            $table->enum('attendance_sentiment', ['positive', 'neutral', 'needs_guidance'])->default('neutral');
            
            // Catatan Sikap / Perilaku
            $table->text('attitude_note')->nullable();
            $table->enum('attitude_sentiment', ['positive', 'neutral', 'needs_guidance'])->default('neutral');
            
            // Tindak Lanjut & Pengaturan
            $table->text('follow_up')->nullable();
            $table->boolean('is_visible_to_parents')->default(false);
            
            $table->timestamps();

            // Index untuk pencarian cepat
            $table->index(['student_id', 'date']);
            $table->index(['schedule_id', 'date']);
            $table->index(['school_class_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_anecdotes');
    }
};
