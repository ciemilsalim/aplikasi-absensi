<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Semester;
use App\Models\AcademicYear;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ambil semua semester yang ada
        $semesters = DB::table('semesters')->get();
        if ($semesters->isEmpty()) {
            return;
        }

        // Cari ID Semester 1 (2025/2026 Genap), Semester 2 (2025/2026 Ganjil), Semester 3 (2026/2027 Ganjil)
        $genap2025 = DB::table('semesters')->where('id', 1)->first();
        $ganjil2025 = DB::table('semesters')->where('id', 2)->first();
        $ganjil2026 = DB::table('semesters')->where('id', 3)->first();

        // 1. Backfill tabel attendances (Presensi Harian)
        if ($genap2025) {
            DB::table('attendances')
                ->whereNull('semester_id')
                ->where('attendance_time', '>=', '2026-01-01')
                ->where('attendance_time', '<', '2026-07-01')
                ->update([
                    'semester_id' => $genap2025->id,
                    'academic_year_id' => $genap2025->academic_year_id,
                ]);
        }

        if ($ganjil2025) {
            DB::table('attendances')
                ->whereNull('semester_id')
                ->where('attendance_time', '<', '2026-01-01')
                ->update([
                    'semester_id' => $ganjil2025->id,
                    'academic_year_id' => $ganjil2025->academic_year_id,
                ]);
        }

        if ($ganjil2026) {
            DB::table('attendances')
                ->whereNull('semester_id')
                ->where('attendance_time', '>=', '2026-07-01')
                ->update([
                    'semester_id' => $ganjil2026->id,
                    'academic_year_id' => $ganjil2026->academic_year_id,
                ]);
        }

        // 2. Backfill tabel subject_attendances (Presensi Mapel)
        if ($ganjil2025) {
            DB::table('subject_attendances')
                ->whereNull('semester_id')
                ->where('created_at', '<', '2026-01-01')
                ->update([
                    'semester_id' => $ganjil2025->id,
                    'academic_year_id' => $ganjil2025->academic_year_id,
                ]);
        }

        if ($genap2025) {
            DB::table('subject_attendances')
                ->whereNull('semester_id')
                ->where('created_at', '>=', '2026-01-01')
                ->where('created_at', '<', '2026-07-01')
                ->update([
                    'semester_id' => $genap2025->id,
                    'academic_year_id' => $genap2025->academic_year_id,
                ]);
        }

        // 3. Backfill tabel teacher_attendances (Presensi Guru)
        if ($genap2025) {
            DB::table('teacher_attendances')
                ->whereNull('semester_id')
                ->update([
                    'semester_id' => $genap2025->id,
                    'academic_year_id' => $genap2025->academic_year_id,
                ]);
        }

        // 4. Backfill tabel calendars
        if ($genap2025) {
            DB::table('calendars')
                ->whereNull('semester_id')
                ->update([
                    'semester_id' => $genap2025->id,
                    'academic_year_id' => $genap2025->academic_year_id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for data backfill
    }
};
