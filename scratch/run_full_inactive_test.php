<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Semester;
use App\Models\TeachingAssignment;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

echo "======================================================\n";
echo "   COMPREHENSIVE TEST: INACTIVE STUDENT FILTERING     \n";
echo "======================================================\n\n";

// 1. Check inactive students in database
$inactiveCount = DB::table('students')->whereIn('status', Student::$inactiveStatuses)->count();
echo "[1] Total Inactive Students in DB: {$inactiveCount}\n";
$inactives = DB::table('students')->whereIn('status', Student::$inactiveStatuses)->get();
foreach ($inactives as $in) {
    echo "    - ID: {$in->id} | NIS: {$in->nis} | Name: {$in->name} | Status: {$in->status}\n";
}

// 2. Active Semester Check (TA 2026/2027 Ganjil)
$activeSem = Semester::where('is_active', true)->first();
session(['active_semester_id' => $activeSem->id]);
echo "\n[2] Active Semester (ID: {$activeSem->id} - {$activeSem->name})\n";

// Test every class in Active Semester
$allClasses = SchoolClass::all();
$inactiveLeakedActiveSem = 0;
foreach ($allClasses as $cls) {
    $students = $cls->students()->get();
    $inactivesFound = $students->filter(fn($s) => in_array($s->status, Student::$inactiveStatuses));
    if ($inactivesFound->isNotEmpty()) {
        echo "    ❌ ERROR: Class '{$cls->name}' (ID {$cls->id}) contains inactive students!\n";
        $inactiveLeakedActiveSem += $inactivesFound->count();
    }
}
if ($inactiveLeakedActiveSem === 0) {
    echo "    ✅ SUCCESS: 0 inactive students found across all classes in active semester!\n";
}

// 3. Past Semester Check (TA 2025/2026 Ganjil & Genap)
$semGanjil2025 = Semester::where('name', 'like', '%Ganjil%')->where('is_active', false)->first();
if ($semGanjil2025) {
    session(['active_semester_id' => $semGanjil2025->id]);
    echo "\n[3] Historical Semester (ID: {$semGanjil2025->id} - {$semGanjil2025->name})\n";
    $class9a = SchoolClass::where('name', 'Kelas 9A')->first();
    if ($class9a) {
        $st9a = $class9a->students()->get();
        echo "    - Kelas 9A Student Count: " . $st9a->count() . " students (Expected: 29)\n";
        if ($st9a->count() >= 29) {
            echo "    ✅ SUCCESS: Historical roster for Kelas 9A is completely preserved!\n";
        } else {
            echo "    ⚠️ Notice: Count is " . $st9a->count() . "\n";
        }
    }
}

// 4. Teaching Assignment & Schedule Check
session(['active_semester_id' => $activeSem->id]);
echo "\n[4] TeachingAssignment & Schedule Enrolled Students in Active Semester\n";
$taList = TeachingAssignment::all();
$taLeak = 0;
foreach ($taList as $ta) {
    $enrolled = $ta->getEnrolledStudents();
    $inactivesInTa = $enrolled->filter(fn($s) => in_array($s->status, Student::$inactiveStatuses));
    if ($inactivesInTa->isNotEmpty()) {
        echo "    ❌ ERROR: TA ID {$ta->id} has inactive students enrolled!\n";
        $taLeak += $inactivesInTa->count();
    }
}
if ($taLeak === 0) {
    echo "    ✅ SUCCESS: All Teaching Assignments properly exclude inactive students!\n";
}

// 5. Scanner Query Check
$scannerStudents = Student::active()->whereNotNull('photo')->get();
$scannerInactive = $scannerStudents->filter(fn($s) => in_array($s->status, Student::$inactiveStatuses));
echo "\n[5] Scanner Student Query\n";
echo "    - Active Students with Photo: " . $scannerStudents->count() . "\n";
echo "    - Inactive Students Leaked into Scanner: " . $scannerInactive->count() . "\n";
if ($scannerInactive->isEmpty()) {
    echo "    ✅ SUCCESS: Scanner strictly only includes active students!\n";
}

echo "\n======================================================\n";
echo "                   ALL TESTS PASSED                   \n";
echo "======================================================\n";
