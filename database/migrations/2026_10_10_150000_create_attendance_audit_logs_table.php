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
        Schema::create('attendance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('student_name');
            $table->string('student_nis')->nullable();
            $table->string('class_name')->nullable();
            $table->date('attendance_date');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('old_notes')->nullable();
            $table->text('new_notes')->nullable();
            $table->text('reason')->nullable();
            $table->string('action_type')->default('manual_correction'); // manual_correction, leave_sync, teacher_override
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name')->nullable();
            $table->string('changed_by_role')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes for fast querying during BOS & Inspektorat audits
            $table->index(['attendance_date', 'new_status']);
            $table->index(['student_id', 'attendance_date']);
            $table->index('action_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_audit_logs');
    }
};
