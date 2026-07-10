<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'school_classes',
            'teaching_assignments',
            'schedules',
            'extracurriculars'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'semester_id')) {
                    $table->unsignedBigInteger('semester_id')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable();
                }
            });
        }

        // Backfill data with current active semester from settings
        $activeSemesterId = DB::table('settings')->where('key', 'active_semester_id')->value('value');
        $activeAcademicYearId = DB::table('settings')->where('key', 'active_academic_year_id')->value('value');

        if ($activeSemesterId) {
            foreach ($tables as $table) {
                DB::table($table)->whereNull('semester_id')->update([
                    'semester_id' => $activeSemesterId,
                    'academic_year_id' => $activeAcademicYearId
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'school_classes',
            'teaching_assignments',
            'schedules',
            'extracurriculars'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'semester_id')) {
                    $table->dropColumn('semester_id');
                }
                if (Schema::hasColumn($table->getTable(), 'academic_year_id')) {
                    $table->dropColumn('academic_year_id');
                }
            });
        }
    }
};
