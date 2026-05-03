<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained()->onDelete('set null'); // Pembina
            $table->timestamps();
        });

        Schema::create('extracurricular_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extracurricular_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['extracurricular_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracurricular_student');
        Schema::dropIfExists('extracurriculars');
    }
};
