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
        Schema::create('teacher_semester_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('school_class_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained()->onDelete('set null');

            // 6 Poin Refleksi Akhir Semester (Bagian F)
            $table->text('good_aspects')->nullable(); // Pembelajaran yang berjalan baik
            $table->text('challenges')->nullable(); // Kendala utama
            $table->text('attention_students')->nullable(); // Peserta didik yang memerlukan perhatian
            $table->text('effective_strategies')->nullable(); // Strategi yang efektif
            $table->text('future_improvements')->nullable(); // Perbaikan pembelajaran berikutnya
            $table->text('follow_up_plan')->nullable(); // Rencana tindak lanjut

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_semester_reflections');
    }
};
