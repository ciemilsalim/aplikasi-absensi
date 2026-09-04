<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TESTING COMPREHENSIVE MULTI-SEMESTER DISCOVERY ===\n";

$year2025 = AcademicYear::where('name', 'like', '%2025%')->first() ?? AcademicYear::orderBy('id')->first();
$year2026 = AcademicYear::where('name', 'like', '%2026%')->first() ?? AcademicYear::orderBy('id', 'desc')->first();

echo "Year 2025: ID {$year2025->id} ({$year2025->name})\n";
echo "Year 2026: ID {$year2026->id} ({$year2026->name})\n";

$semesters2025 = Semester::where('academic_year_id', $year2025->id)->get();
echo "Semesters in 2025/2026: " . $semesters2025->map(fn($s)=>"ID {$s->id} ({$s->name})")->join(', ') . "\n";

// List all classes to ensure
$standardClasses = [
    // Kelas 7 (Level 1)
    'Kelas 7A' => 1,
    'Kelas 7B' => 1,
    'Kelas 7C' => 1,
    'Kelas 7D' => 1,
    'Kelas 7E' => 1,
    // Kelas 8 (Level 2)
    'Kelas 8A' => 2,
    'Kelas 8B' => 2,
    'Kelas 8C' => 2,
    'Kelas 8D' => 2,
    // Kelas 9 (Level 3)
    'Kelas 9A' => 3,
    'Kelas 9B' => 3,
    'Kelas 9C' => 3,
    'Kelas 9D' => 3,
];

echo "\nTarget standard classes to manage: " . count($standardClasses) . " classes.\n";
