<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== student_class_histories COLUMNS ===\n";
print_r(Schema::getColumnListing('student_class_histories'));

echo "\n=== ALL student_class_histories ROWS ===\n";
$rows = DB::table('student_class_histories as sch')
    ->leftJoin('students as st', 'sch.student_id', '=', 'st.id')
    ->leftJoin('school_classes as sc', 'sch.school_class_id', '=', 'sc.id')
    ->select('sch.*', 'st.name as student_name', 'sc.name as class_name')
    ->get();
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== CURRENT class_student ROWS ===\n";
$cs = DB::table('class_student as cs')
    ->leftJoin('students as st', 'cs.student_id', '=', 'st.id')
    ->leftJoin('school_classes as sc', 'cs.school_class_id', '=', 'sc.id')
    ->select('cs.*', 'st.name as student_name', 'sc.name as class_name')
    ->get();
echo "Total: " . $cs->count() . "\n";
foreach ($cs as $r) {
    echo json_encode($r) . "\n";
}
