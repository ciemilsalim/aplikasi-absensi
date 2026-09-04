<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

echo "=== TESTING ATTENDANCE HISTORY FOR KELAS 9A (JULY 2025 & OCTOBER 2025) ===\n\n";

session(['active_academic_year_id' => 1, 'active_semester_id' => 2]); // 2025/2026 Ganjil

$cls9a = SchoolClass::withoutGlobalScopes()->where('name', 'Kelas 9A')->where('academic_year_id', 1)->first();
$students = $cls9a->students()->orderBy('name')->get();

echo "Kelas 9A Students Loaded via Relation: " . $students->count() . "\n";

// Test July 2025
$monthJuly = Carbon::parse('2025-07-01');
$startDateJuly = $monthJuly->copy()->startOfMonth();
$endDateJuly = $monthJuly->copy()->endOfMonth();

$studentIds = $students->pluck('id');

$attJuly = Attendance::withoutGlobalScope('academic_period')
    ->whereIn('student_id', $studentIds)
    ->whereBetween('attendance_time', [$startDateJuly, $endDateJuly])
    ->get();

echo "Total Attendance Records in July 2025 for 9A: " . $attJuly->count() . "\n";

$studentsWithAttJuly = $attJuly->pluck('student_id')->unique()->count();
echo "Distinct Students with Attendance in July 2025: " . $studentsWithAttJuly . " / 29\n";

// Test October 2025
$monthOct = Carbon::parse('2025-10-01');
$startDateOct = $monthOct->copy()->startOfMonth();
$endDateOct = $monthOct->copy()->endOfMonth();

$attOct = Attendance::withoutGlobalScope('academic_period')
    ->whereIn('student_id', $studentIds)
    ->whereBetween('attendance_time', [$startDateOct, $endDateOct])
    ->get();

echo "\nTotal Attendance Records in October 2025 for 9A: " . $attOct->count() . "\n";
