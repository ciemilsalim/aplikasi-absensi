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
        // 1. attendances composite indexes
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasIndex('attendances', 'idx_attendances_student_time')) {
                    $table->index(['student_id', 'attendance_time'], 'idx_attendances_student_time');
                }
                if (!Schema::hasIndex('attendances', 'idx_attendances_time_status')) {
                    $table->index(['attendance_time', 'status'], 'idx_attendances_time_status');
                }
            });
        }

        // 2. subject_attendances indexes
        if (Schema::hasTable('subject_attendances')) {
            Schema::table('subject_attendances', function (Blueprint $table) {
                if (!Schema::hasIndex('subject_attendances', 'idx_subj_att_teacher_status')) {
                    $table->index(['teacher_id', 'status', 'created_at'], 'idx_subj_att_teacher_status');
                }
                if (!Schema::hasIndex('subject_attendances', 'idx_subj_att_sched_created')) {
                    $table->index(['schedule_id', 'created_at'], 'idx_subj_att_sched_created');
                }
            });
        }

        // 3. extracurricular_attendances indexes
        if (Schema::hasTable('extracurricular_attendances')) {
            Schema::table('extracurricular_attendances', function (Blueprint $table) {
                if (!Schema::hasIndex('extracurricular_attendances', 'idx_ekskul_att_date_status')) {
                    $table->index(['extracurricular_id', 'attendance_date', 'status'], 'idx_ekskul_att_date_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (Schema::hasIndex('attendances', 'idx_attendances_student_time')) {
                    $table->dropIndex('idx_attendances_student_time');
                }
                if (Schema::hasIndex('attendances', 'idx_attendances_time_status')) {
                    $table->dropIndex('idx_attendances_time_status');
                }
            });
        }

        if (Schema::hasTable('subject_attendances')) {
            Schema::table('subject_attendances', function (Blueprint $table) {
                if (Schema::hasIndex('subject_attendances', 'idx_subj_att_teacher_status')) {
                    $table->dropIndex('idx_subj_att_teacher_status');
                }
                if (Schema::hasIndex('subject_attendances', 'idx_subj_att_sched_created')) {
                    $table->dropIndex('idx_subj_att_sched_created');
                }
            });
        }

        if (Schema::hasTable('extracurricular_attendances')) {
            Schema::table('extracurricular_attendances', function (Blueprint $table) {
                if (Schema::hasIndex('extracurricular_attendances', 'idx_ekskul_att_date_status')) {
                    $table->dropIndex('idx_ekskul_att_date_status');
                }
            });
        }
    }
};
