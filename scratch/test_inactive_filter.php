<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Semester;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

echo "=== TESTING ACTIVE SEMESTER VS HISTORICAL SEMESTER FOR INACTIVE STUDENTS ===\n\n";

$activeSem = Semester::where('is_active', true)->first();
echo "Active Semester: ID {$activeSem->id} - {$activeSem->name}\n";

$sem2025Ganjil = Semester::where('name', 'like', '%Ganjil%')->where('is_active', false)->first();
echo "Past Semester (2025 Ganjil): ID " . ($sem2025Ganjil?->id ?? 'none') . " - " . ($sem2025Ganjil?->name ?? 'none') . "\n\n";

// Let's check Kelas 9A in active semester:
session(['active_semester_id' => $activeSem->id]);
$class9aActive = SchoolClass::where('name', 'Kelas 9A')->where('academic_year_id', $activeSem->academic_year_id)->first() 
    ?? SchoolClass::where('name', 'Kelas 9A')->first();

if ($class9aActive) {
    echo "Kelas 9A (Active Sem ID: {$activeSem->id}):\n";
    $students = $class9aActive->students()->get();
    echo "Count of students in 9A (active sem): " . $students->count() . "\n";
    $inactiveIn9a = $students->filter(fn($s) => in_array($s->status, Student::$inactiveStatuses ?? ['tidak_aktif']));
    echo "Inactive students returned: " . $inactiveIn9a->count() . "\n";
}

// Let's test with past semester
if ($sem2025Ganjil) {
    session(['active_semester_id' => $sem2025Ganjil->id]);
    $class9aPast = SchoolClass::where('name', 'Kelas 9A')->where('academic_year_id', $sem2025Ganjil->academic_year_id)->first()
        ?? SchoolClass::where('name', 'Kelas 9A')->first();
    if ($class9aPast) {
        echo "\nKelas 9A (Past Sem ID: {$sem2025Ganjil->id}):\n";
        $studentsPast = $class9aPast->students()->get();
        echo "Count of students in 9A (past sem): " . $studentsPast->count() . "\n";
    }
}
