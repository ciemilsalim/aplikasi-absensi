<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SyncDatabaseHpanel extends Command
{
    protected $signature = 'siasek:sync-database';
    protected $description = 'Sinkronisasi lengkap database presensi dan normalisasi semester/kelas';

    public function handle()
    {
        $this->info('Memulai sinkronisasi database SIASEK...');
        $now = now();

        $years2025 = AcademicYear::where('name', 'like', '%2025%')
            ->orWhere('id', 1)
            ->get();

        if ($years2025->isEmpty()) {
            $years2025 = AcademicYear::orderBy('id')->take(1)->get();
        }

        $year2025 = $years2025->first();
        $year2025Ids = $years2025->pluck('id')->toArray();

        $year2026 = AcademicYear::where('name', 'like', '%2026%')
            ->whereNotIn('id', $year2025Ids)
            ->orderBy('id', 'desc')
            ->first();

        $lockedYearId = $year2026?->id;

        $semesters2025 = Semester::whereIn('academic_year_id', $year2025Ids)
            ->where(function($q) use ($lockedYearId) {
                if ($lockedYearId) {
                    $q->where('academic_year_id', '!=', $lockedYearId);
                }
                $q->where('is_active', '!=', 1);
            })
            ->get();

        $orphanSemesters = Semester::whereNull('academic_year_id')
            ->where('is_active', '!=', 1)
            ->get();

        foreach ($orphanSemesters as $osem) {
            try {
                DB::table('semesters')->where('id', $osem->id)->update(['academic_year_id' => $year2025->id]);
                $osem->academic_year_id = $year2025->id;
                if (!$semesters2025->contains('id', $osem->id)) {
                    $semesters2025->push($osem);
                }
            } catch (\Throwable $e) {}
        }

        $locked2026StudentIds = DB::table('students')
            ->where(function($q) {
                $q->where('created_at', '>=', '2026-07-01')
                  ->orWhere('nis', 'like', 'TEST%');
            })
            ->pluck('id')
            ->toArray();

        if (!empty($locked2026StudentIds)) {
            DB::table('class_student')
                ->whereIn('academic_year_id', $year2025Ids)
                ->whereIn('student_id', $locked2026StudentIds)
                ->delete();
        }

        if ($lockedYearId) {
            $lockedSemIds = DB::table('semesters')->where('academic_year_id', $lockedYearId)->pluck('id')->toArray();
            if (!empty($lockedSemIds)) {
                DB::table('class_student')
                    ->whereIn('academic_year_id', $year2025Ids)
                    ->whereIn('semester_id', $lockedSemIds)
                    ->delete();
            }
        }

        $this->info("Filter tahun 2025/2026 dan 2026/2027 berhasil diproses.");

        // Bersihkan cache
        Artisan::call('optimize:clear');
        $this->info('Sinkronisasi selesai.');
        return 0;
    }
}
