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
        Schema::table('leave_requests', function (Blueprint $table) {
            // Ubah parent_id menjadi nullable agar Admin/TU bisa input izin manual walaupun akun ortu belum terhubung
            if (Schema::hasColumn('leave_requests', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->change();
            }

            // Sumber pengajuan izin (contoh: WhatsApp, Telepon, Surat, Lisan, Aplikasi Orang Tua)
            if (!Schema::hasColumn('leave_requests', 'submission_source')) {
                $table->string('submission_source', 50)->default('aplikasi_ortu')->after('type');
            }

            // Staf/User (Admin/TU) yang menginput secara manual
            if (!Schema::hasColumn('leave_requests', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('approved_by')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('leave_requests', 'submission_source')) {
                $table->dropColumn('submission_source');
            }
        });
    }
};
