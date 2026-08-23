<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Tabel antrean verifikasi klaim siswa oleh orang tua
        if (!Schema::hasTable('parent_student_requests')) {
            Schema::create('parent_student_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->string('relationship')->default('Orang Tua');
                $table->string('verification_code')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tambahkan status onboarding & info pendukung pada tabel parents
        Schema::table('parents', function (Blueprint $table) {
            if (!Schema::hasColumn('parents', 'is_onboarding_completed')) {
                $table->boolean('is_onboarding_completed')->default(false)->after('phone_number');
            }
            if (!Schema::hasColumn('parents', 'address')) {
                $table->text('address')->nullable()->after('is_onboarding_completed');
            }
        });
    }

    public function down(): void {
        Schema::dropIfExists('parent_student_requests');
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'is_onboarding_completed')) {
                $table->dropColumn('is_onboarding_completed');
            }
            if (Schema::hasColumn('parents', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
