<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ACADEMIC YEARS ===\n";
foreach (DB::table('academic_years')->get() as $ay) {
    echo json_encode($ay) . "\n";
}

echo "\n=== SEMESTERS ===\n";
foreach (DB::table('semesters')->get() as $s) {
    echo json_encode($s) . "\n";
}
