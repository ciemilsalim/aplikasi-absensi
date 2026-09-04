<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Semester;

$cls = SchoolClass::find(10);
session(['active_semester_id' => 3]);

$testStatuses = [
    'tidak_aktif',
    'tidak aktif',
    'nonaktif',
    'non_aktif',
    'non aktif',
    'inactive',
    'mutasi',
    'keluar',
    'lulus',
    'pindah',
    'berhenti',
    'drop_out',
    'dropout',
    'alumni'
];

echo "=== TESTING ALL INACTIVE STATUS STRINGS ===\n";
foreach ($testStatuses as $status) {
    Student::where('id', 6)->update(['status' => $status]);
    $students = $cls->students()->get();
    $found = $students->contains('id', 6);
    echo "Status '{$status}': " . ($found ? "FAILED (Leaked into list!)" : "PASSED (Properly hidden)") . "\n";
}

// Reset student back to aktif
Student::where('id', 6)->update(['status' => 'aktif']);
echo "\nReset student 6 back to aktif: " . ($cls->students()->get()->contains('id', 6) ? "PASSED (Visible again)" : "FAILED") . "\n";
