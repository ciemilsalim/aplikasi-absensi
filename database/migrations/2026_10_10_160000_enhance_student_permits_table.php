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
        Schema::table('student_permits', function (Blueprint $table) {
            if (!Schema::hasColumn('student_permits', 'permit_type')) {
                $table->string('permit_type')->default('pulang_cepat')->after('reason');
            }
            if (!Schema::hasColumn('student_permits', 'pass_code')) {
                $table->string('pass_code')->nullable()->unique()->after('permit_type');
            }
            if (!Schema::hasColumn('student_permits', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->after('pass_code')->constrained('users')->nullOnDelete();
                $table->string('approved_by_name')->nullable()->after('approved_by_user_id');
            }
            if (!Schema::hasColumn('student_permits', 'security_user_id')) {
                $table->foreignId('security_user_id')->nullable()->after('approved_by_name')->constrained('users')->nullOnDelete();
                $table->string('security_name')->nullable()->after('security_user_id');
            }
            if (!Schema::hasColumn('student_permits', 'status')) {
                $table->string('status')->default('checked_out')->after('security_name'); // approved, checked_out, completed, cancelled
            }
            if (!Schema::hasColumn('student_permits', 'valid_until')) {
                $table->timestamp('valid_until')->nullable()->after('time_in');
            }
            if (!Schema::hasColumn('student_permits', 'notes')) {
                $table->text('notes')->nullable()->after('valid_until');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_permits', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['security_user_id']);
            $table->dropColumn([
                'permit_type',
                'pass_code',
                'approved_by_user_id',
                'approved_by_name',
                'security_user_id',
                'security_name',
                'status',
                'valid_until',
                'notes',
            ]);
        });
    }
};
