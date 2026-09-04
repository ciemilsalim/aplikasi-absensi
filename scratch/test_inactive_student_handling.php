<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

echo "=== TESTING INACTIVE STUDENT FILTERING IN ACTIVE VS HISTORICAL PERIOD ===\n\n";

// Let's inspect Student 151 (Adi Nugraha) who has status = 'tidak_aktif'
$adi = Student::find(151);
echo "Student 151: {$adi->name} | NIS: {$adi->nis} | Status: {$adi->status} | ClassID: {$adi->school_class_id}\n\n";

// Test 1: In Active Semester (TA 2026/2027 Ganjil - Semester 3)
session(['active_academic_year_id' => 2, 'active_semester_id' => 3]);

// If we check a class, e.g. Class 9 or Class 10
$class9 = SchoolClass::withoutGlobalScopes()->find(9); // XI-1 (where Adi was)
echo "Testing Class ID 9 (XI-1) in Active Semester (AY 2, Sem 3):\n";
echo "Total without status filter: " . Student::where('school_class_id', 9)->count() . "\n";
echo "Total active only: " . Student::where('school_class_id', 9)->where(function($q) {
    $q->where('status', 'aktif')->orWhereNull('status');
})->count() . "\n";

// Test 2: In Historical Semester (TA 2025/2026 Ganjil - Semester 2)
session(['active_academic_year_id' => 1, 'active_semester_id' => 2]);
$cls9a = SchoolClass::withoutGlobalScopes()->where('name', 'Kelas 9A')->where('academic_year_id', 1)->first();
echo "\nTesting Kelas 9A in Historical Semester (AY 1, Sem 2):\n";
echo "Total students in 9A: " . $cls9a->students()->count() . "\n";
