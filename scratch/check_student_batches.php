<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$all = DB::table('students')->orderBy('id')->get();
echo "Total students: " . $all->count() . "\n\n";

$groups = [];
foreach ($all as $s) {
    $date = substr($s->created_at, 0, 7);
    $groups[$date][] = $s;
}

foreach ($groups as $date => $list) {
    echo "=== DATE GROUP: {$date} (Count: " . count($list) . ") ===\n";
    $first = $list[0];
    $last = end($list);
    echo "ID Range: {$first->id} .. {$last->id} | NIS: {$first->nis} .. {$last->nis}\n";
    echo "Sample names: {$first->name}, {$last->name}\n\n";
}

echo "=== CHECK ALL STUDENTS WITH NIS PREFIXES ===\n";
foreach ($all as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | Status: {$s->status} | ClassID: {$s->school_class_id} | Created: {$s->created_at}\n";
}
