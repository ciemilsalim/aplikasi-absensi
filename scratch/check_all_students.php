<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TABLES ===\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $arr = (array)$t;
    echo array_values($arr)[0] . "\n";
}

echo "\n=== ALL STUDENTS (NIS, NAME, CLASS_ID, CREATED_AT, STATUS) ===\n";
$students = DB::table('students')->orderBy('id')->get();
foreach ($students as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | ClassID: {$s->school_class_id} | Status: {$s->status} | Created: {$s->created_at}\n";
}
