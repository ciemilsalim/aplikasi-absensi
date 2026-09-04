<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Student;

echo "=================================================================\n";
echo "   TESTING FRESH HTTP REQUEST SIMULATION                         \n";
echo "=================================================================\n";

// Scenario 1: HTTP Request with active_semester_id = 1 (2025/2026 Genap, Year 1)
session(['active_semester_id' => 1, 'active_academic_year_id' => 1]);
$teacher1 = Teacher::find(1);
$class1 = $teacher1->homeroomClass;
echo "Request 1 (Year 1): " . ($class1 ? "Class ID {$class1->id} - {$class1->name} (Year: {$class1->academic_year_id})" : "NONE") . "\n";
if ($class1) {
    echo "  Students: " . $class1->students()->pluck('name')->join(', ') . "\n";
}

// Scenario 2: HTTP Request with active_semester_id = 3 (2026/2027 Ganjil, Year 2)
session(['active_semester_id' => 3, 'active_academic_year_id' => 2]);
$teacher2 = Teacher::find(1);
$class2 = $teacher2->homeroomClass;
echo "\nRequest 2 (Year 2): " . ($class2 ? "Class ID {$class2->id} - {$class2->name} (Year: {$class2->academic_year_id})" : "NONE") . "\n";
if ($class2) {
    echo "  Students: " . $class2->students()->pluck('name')->join(', ') . "\n";
}
