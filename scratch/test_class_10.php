<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

// Let's set a student in Class 10 to tidak_aktif
$sampleStudent = Student::where('school_class_id', 10)->first();
if ($sampleStudent) {
    echo "Found student in Class 10: ID {$sampleStudent->id}, Name: {$sampleStudent->name}, Old status: {$sampleStudent->status}\n";
    $sampleStudent->update(['status' => 'tidak_aktif']);
}

// Now let's test SchoolClass::find(10)->students() in active semester vs non-active
session(['active_semester_id' => 3]);
$cls = SchoolClass::find(10);
$allClassStudents = $cls->students()->get();
echo "Students in Class 10 with active semester (ID 3): " . $allClassStudents->count() . "\n";
foreach ($allClassStudents as $s) {
    echo " - ID: {$s->id} | Name: {$s->name} | Status: {$s->status}\n";
}

// Check if inactive student is present
$hasInactive = $allClassStudents->contains('id', $sampleStudent->id);
echo "Is inactive student {$sampleStudent->name} in list? " . ($hasInactive ? "YES (BUG!)" : "NO (CORRECT! FILTERED OUT)") . "\n";
