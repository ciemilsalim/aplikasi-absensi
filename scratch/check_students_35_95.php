<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== STUDENTS ID 35 to 95 ===\n";
$students = DB::table('students')->whereBetween('id', [35, 95])->get();
foreach ($students as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | ClassID: {$s->school_class_id} | Status: {$s->status} | Created: {$s->created_at}\n";
}
