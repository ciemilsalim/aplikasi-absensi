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
