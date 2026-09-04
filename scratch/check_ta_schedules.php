<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEACHING ASSIGNMENTS ===\n";
$ta = DB::table('teaching_assignments')->get();
foreach ($ta as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== SCHEDULES ===\n";
$sch = DB::table('schedules')->get();
foreach ($sch as $r) {
    echo json_encode($r) . "\n";
}
