<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK ALL STUDENTS CREATED IN 2025/2026 (created_at < 2026-07-01) ===\n";

$students2025 = DB::table('students')
    ->where('created_at', '<', '2026-07-01')
    ->where('nis', 'not like', 'TEST%')
    ->orderBy('id')
    ->get();

echo "Total students created in 2025/2026: " . $students2025->count() . "\n\n";

// Check distinct batches or classes
$byNISPrefix = [];
foreach ($students2025 as $s) {
    $prefix = substr($s->nis, 0, 4);
    $byNISPrefix[$prefix][] = $s;
}

foreach ($byNISPrefix as $prefix => $list) {
    echo "Prefix: {$prefix} | Count: " . count($list) . " | Sample: " . $list[0]->nis . " - " . $list[0]->name . "\n";
}

echo "\n=== LET'S LIST ALL 2025 STUDENTS WITH THEIR DETAILED INFO ===\n";
foreach ($students2025 as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | ClassID: {$s->school_class_id} | Status: {$s->status} | Created: {$s->created_at}\n";
}
