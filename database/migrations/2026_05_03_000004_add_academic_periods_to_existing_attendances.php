<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel attendances (Absensi Harian Siswa)
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
        });

        // 2. Tabel subject_attendances (Absensi Mapel Siswa)
        Schema::table('subject_attendances', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
        });

        // 3. Tabel teacher_attendances (Absensi Guru)
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['academic_year_id', 'semester_id']);
        });

        Schema::table('subject_attendances', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['academic_year_id', 'semester_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['academic_year_id', 'semester_id']);
        });
    }
};
