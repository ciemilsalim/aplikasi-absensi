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
        Schema::table('teaching_journals', function (Blueprint $table) {
            // Kolom pelengkap relasi & scope
            if (!Schema::hasColumn('teaching_journals', 'school_class_id')) {
                $table->foreignId('school_class_id')->nullable()->after('teacher_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('teaching_journals', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('school_class_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('teaching_journals', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('subject_id')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('teaching_journals', 'semester_id')) {
                $table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained()->onDelete('set null');
            }

            // 10 Field Inti Format Jurnal SMPN 1 Biau TP 2026/2027
            if (!Schema::hasColumn('teaching_journals', 'jp')) {
                $table->integer('jp')->default(2)->after('date'); // Jumlah Jam Pelajaran
            }
            if (!Schema::hasColumn('teaching_journals', 'learning_objective')) {
                $table->text('learning_objective')->nullable()->after('jp'); // Tujuan Pembelajaran (TP)
            }
            if (!Schema::hasColumn('teaching_journals', 'topic')) {
                $table->string('topic')->nullable()->after('learning_objective'); // Topik / Materi
            }
            if (!Schema::hasColumn('teaching_journals', 'activity')) {
                $table->text('activity')->nullable()->after('topic'); // Kegiatan Pembelajaran
            }
            if (!Schema::hasColumn('teaching_journals', 'assessment')) {
                $table->string('assessment')->nullable()->after('activity'); // Bentuk Asesmen (Observasi, LKPD, dll)
            }
            if (!Schema::hasColumn('teaching_journals', 'reflection')) {
                $table->text('reflection')->nullable()->after('assessment'); // Hasil / Refleksi Pembelajaran
            }
            if (!Schema::hasColumn('teaching_journals', 'follow_up')) {
                $table->text('follow_up')->nullable()->after('reflection'); // Tindak Lanjut (Remedial, Pengayaan, dll)
            }

            // Statistik Capaian Siswa (Bagian E)
            if (!Schema::hasColumn('teaching_journals', 'students_achieved_count')) {
                $table->integer('students_achieved_count')->nullable()->after('follow_up');
            }
            if (!Schema::hasColumn('teaching_journals', 'students_remedial_count')) {
                $table->integer('students_remedial_count')->nullable()->after('students_achieved_count');
            }
            if (!Schema::hasColumn('teaching_journals', 'students_enrichment_count')) {
                $table->integer('students_enrichment_count')->nullable()->after('students_remedial_count');
            }

            // Snapshot Kehadiran Siswa dari Presensi Mapel
            if (!Schema::hasColumn('teaching_journals', 'attendance_hadir')) {
                $table->integer('attendance_hadir')->default(0)->after('students_enrichment_count');
            }
            if (!Schema::hasColumn('teaching_journals', 'attendance_sakit')) {
                $table->integer('attendance_sakit')->default(0)->after('attendance_hadir');
            }
            if (!Schema::hasColumn('teaching_journals', 'attendance_izin')) {
                $table->integer('attendance_izin')->default(0)->after('attendance_sakit');
            }
            if (!Schema::hasColumn('teaching_journals', 'attendance_alpa')) {
                $table->integer('attendance_alpa')->default(0)->after('attendance_izin');
            }

            // Supervisi & Verifikasi
            if (!Schema::hasColumn('teaching_journals', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('notes');
            }
            if (!Schema::hasColumn('teaching_journals', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('is_verified')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('teaching_journals', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('teaching_journals', 'supervisor_notes')) {
                $table->text('supervisor_notes')->nullable()->after('verified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            $columns = [
                'school_class_id', 'subject_id', 'academic_year_id', 'semester_id',
                'jp', 'learning_objective', 'topic', 'activity', 'assessment', 'reflection', 'follow_up',
                'students_achieved_count', 'students_remedial_count', 'students_enrichment_count',
                'attendance_hadir', 'attendance_sakit', 'attendance_izin', 'attendance_alpa',
                'is_verified', 'verified_by', 'verified_at', 'supervisor_notes'
            ];
            $table->dropColumn($columns);
        });
    }
};
