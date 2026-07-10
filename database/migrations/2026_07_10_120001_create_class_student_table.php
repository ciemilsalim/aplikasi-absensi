<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_student')) {
            Schema::create('class_student', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('school_class_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->unsignedBigInteger('semester_id')->nullable();
                $table->timestamps();
                
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                $table->foreign('school_class_id')->references('id')->on('school_classes')->onDelete('cascade');
                
                $table->unique(['student_id', 'semester_id', 'academic_year_id'], 'student_semester_unique');
            });
        }

        // Backfill data from current students table
        $activeSemesterId = DB::table('settings')->where('key', 'active_semester_id')->value('value');
        $activeAcademicYearId = DB::table('settings')->where('key', 'active_academic_year_id')->value('value');

        if ($activeSemesterId) {
            $students = DB::table('students')->whereNotNull('school_class_id')->get();
            foreach ($students as $student) {
                DB::table('class_student')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $activeSemesterId,
                        'academic_year_id' => $activeAcademicYearId
                    ],
                    [
                        'school_class_id' => $student->school_class_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student');
    }
};
