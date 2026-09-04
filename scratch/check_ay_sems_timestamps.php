<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

echo "=== CHECK ALL ACADEMIC YEARS & SEMESTERS ===\n";

$years = AcademicYear::all();
foreach ($years as $y) {
    echo "Year ID: {$y->id} | Name: {$y->name} | is_active: " . ($y->is_active ?? 'N/A') . "\n";
    $sems = Semester::where('academic_year_id', $y->id)->get();
    foreach ($sems as $s) {
        echo "   -> Sem ID: {$s->id} | Name: {$s->name} | is_active: " . ($s->is_active ?? 'N/A') . " | Created: {$s->created_at}\n";
    }
}
