<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLASS_STUDENT TABLE STRUCTURE ===\n";
$create = DB::select('SHOW CREATE TABLE class_student');
print_r($create);

echo "\n=== CLASS_STUDENT INDEXES ===\n";
$indexes = DB::select('SHOW INDEX FROM class_student');
print_r($indexes);
