<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$students = DB::table('students')->where('id', '<=', 45)->orderBy('id')->get();
foreach ($students as $st) {
    echo "Student ID {$st->id} | Name: {$st->name} | NIS: {$st->nis} | Direct Class: {$st->school_class_id}\n";
}
