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
        // Align students table columns
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'religion')) {
                    $table->string('religion', 50)->default('islam')->after('school_class_id');
                }
                if (!Schema::hasColumn('students', 'learning_email')) {
                    $table->string('learning_email')->nullable()->unique()->after('nis');
                }
                if (!Schema::hasColumn('students', 'status')) {
                    $table->string('status', 20)->default('aktif')->after('religion');
                }
            });
        }

        // Align teaching_assignments table columns
        if (Schema::hasTable('teaching_assignments')) {
            Schema::table('teaching_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('teaching_assignments', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable()->after('teacher_id');
                }
                if (!Schema::hasColumn('teaching_assignments', 'semester_id')) {
                    $table->unsignedBigInteger('semester_id')->nullable()->after('teacher_id');
                }
            });
        }

        // Align schedules table columns
        if (Schema::hasTable('schedules')) {
            Schema::table('schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('schedules', 'teacher_id')) {
                    $table->unsignedBigInteger('teacher_id')->nullable();
                }
                if (!Schema::hasColumn('schedules', 'school_class_id')) {
                    $table->unsignedBigInteger('school_class_id')->nullable();
                }
                if (!Schema::hasColumn('schedules', 'schedule_type')) {
                    $table->string('schedule_type')->default('regular');
                }
                if (!Schema::hasColumn('schedules', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable();
                }
                if (!Schema::hasColumn('schedules', 'semester_id')) {
                    $table->unsignedBigInteger('semester_id')->nullable();
                }
                if (!Schema::hasColumn('schedules', 'cocurricular_id')) {
                    $table->unsignedBigInteger('cocurricular_id')->nullable();
                }
            });
        }

        // Align school_classes table columns
        if (Schema::hasTable('school_classes')) {
            Schema::table('school_classes', function (Blueprint $table) {
                if (!Schema::hasColumn('school_classes', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable();
                }
                if (!Schema::hasColumn('school_classes', 'semester_id')) {
                    $table->unsignedBigInteger('semester_id')->nullable();
                }
            });
        }

        // Align extracurriculars table columns
        if (Schema::hasTable('extracurriculars')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                if (!Schema::hasColumn('extracurriculars', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable();
                }
                if (!Schema::hasColumn('extracurriculars', 'semester_id')) {
                    $table->unsignedBigInteger('semester_id')->nullable();
                }
            });
        }

        // Create cocurriculars table if not exists
        if (!Schema::hasTable('cocurriculars')) {
            Schema::create('cocurriculars', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->unsignedBigInteger('level_id')->nullable();
                $table->string('code')->nullable();
                $table->string('title');
                $table->string('activity_type')->default('pembelajaran_kolaboratif');
                $table->json('dimensions')->nullable();
                $table->integer('time_allocation')->default(0);
                $table->text('learning_objectives')->nullable();
                $table->timestamps();
            });
        }

        // Create cocurricular_teacher pivot table if not exists
        if (!Schema::hasTable('cocurricular_teacher')) {
            Schema::create('cocurricular_teacher', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cocurricular_id');
                $table->unsignedBigInteger('teacher_id');
                $table->timestamps();

                $table->foreign('cocurricular_id')->references('id')->on('cocurriculars')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            });
        }

        // Create teaching_assignment_student pivot table if not exists
        if (!Schema::hasTable('teaching_assignment_student')) {
            Schema::create('teaching_assignment_student', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('teaching_assignment_id');
                $table->unsignedBigInteger('student_id');
                $table->timestamps();

                $table->foreign('teaching_assignment_id')->references('id')->on('teaching_assignments')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cocurricular_teacher');
        Schema::dropIfExists('cocurriculars');

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'religion')) {
                    $table->dropColumn('religion');
                }
                if (Schema::hasColumn('students', 'learning_email')) {
                    $table->dropColumn('learning_email');
                }
                if (Schema::hasColumn('students', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
