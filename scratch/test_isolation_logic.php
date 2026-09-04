<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

echo "=== TESTING ISOLATION & ACCURATE MAPPING LOGIC ===\n\n";

// 1. Identify Academic Years
$year2025 = AcademicYear::where('name', 'like', '%2025%')->orWhere('id', 1)->first();
$year2026 = AcademicYear::where('name', 'like', '%2026%')->where('id', '!=', $year2025->id)->first();

echo "Year 2025/2026: ID {$year2025->id} ({$year2025->name})\n";
echo "Year 2026/2027 (LOCKED): ID " . ($year2026 ? "{$year2026->id} ({$year2026->name})" : "N/A") . "\n\n";

// 2. Identify 2025/2026 Semesters (STRICT: exclude any 2026/2027 semester)
$semesters2025 = Semester::where('academic_year_id', $year2025->id)
    ->where(function($q) use ($year2026) {
        if ($year2026) {
            $q->where('academic_year_id', '!=', $year2026->id);
        }
    })
    ->get();

echo "2025/2026 Semesters:\n";
foreach ($semesters2025 as $sem) {
    echo " - ID {$sem->id}: {$sem->name} (AY_ID: {$sem->academic_year_id})\n";
}

// 3. Check 2026/2027 Excluded Students
$excluded2026StudentIds = DB::table('students')
    ->where('created_at', '>=', '2026-07-01')
    ->orWhere('nis', 'like', 'TEST%')
    ->pluck('id')
    ->toArray();

echo "\n2026/2027 Excluded Students Count: " . count($excluded2026StudentIds) . "\n";
echo "Excluded IDs: " . implode(', ', $excluded2026StudentIds) . "\n\n";

// 4. Test 9A Roster (NIS 1200..1228)
$s9a = DB::table('students')
    ->whereBetween('nis', ['1200', '1228'])
    ->whereNotIn('id', $excluded2026StudentIds)
    ->get();

echo "Kelas 9A Students (NIS 1200..1228) Count: " . $s9a->count() . "\n";
foreach ($s9a as $s) {
    echo " - NIS: {$s->nis} | {$s->name}\n";
}
