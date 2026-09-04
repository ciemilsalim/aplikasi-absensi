<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Student;

echo "=================================================================\n";
echo "   TESTING HOMEROOM TRANSITION ACROSS ACADEMIC YEARS             \n";
echo "=================================================================\n";

// Teacher ID 1 is homeroom in 9A (Year 1, 2025/2026) and 8A (Year 2, 2026/2027)
$teacher = Teacher::find(1);
echo "Testing Teacher: ID {$teacher->id} ({$teacher->name})\n\n";

// --- SCENARIO 1: SWITCH TO 2025/2026 GENAP (Semester 1, Year 1) ---
echo "--- SCENARIO 1: Viewing 2025/2026 Genap (Semester 1, Year 1) ---\n";
session(['active_semester_id' => 1, 'active_academic_year_id' => 1]);

$homeroomClassSem1 = $teacher->homeroomClass;
echo "Homeroom Class: " . ($homeroomClassSem1 ? "Class ID {$homeroomClassSem1->id} - {$homeroomClassSem1->name} (Year: {$homeroomClassSem1->academic_year_id})" : "NONE") . "\n";
if ($homeroomClassSem1) {
    $students = $homeroomClassSem1->students()->get();
    echo "Students in Homeroom (Count: " . $students->count() . "): " . $students->pluck('name')->join(', ') . "\n";
}

// --- SCENARIO 2: SWITCH TO 2026/2027 GANJIL (Semester 3, Year 2) ---
echo "\n--- SCENARIO 2: Viewing 2026/2027 Ganjil (Semester 3, Year 2) ---\n";
session(['active_semester_id' => 3, 'active_academic_year_id' => 2]);

$homeroomClassSem3 = $teacher->homeroomClass;
echo "Homeroom Class: " . ($homeroomClassSem3 ? "Class ID {$homeroomClassSem3->id} - {$homeroomClassSem3->name} (Year: {$homeroomClassSem3->academic_year_id})" : "NONE") . "\n";
if ($homeroomClassSem3) {
    $students = $homeroomClassSem3->students()->get();
    echo "Students in Homeroom (Count: " . $students->count() . "): " . $students->pluck('name')->join(', ') . "\n";
}
