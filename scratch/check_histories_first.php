<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('student_class_histories as sch')
    ->leftJoin('students as st', 'sch.student_id', '=', 'st.id')
    ->leftJoin('school_classes as sc', 'sch.school_class_id', '=', 'sc.id')
    ->select('sch.*', 'st.name as student_name', 'sc.name as class_name')
    ->where('sch.id', '<=', 46)
    ->get();
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}
