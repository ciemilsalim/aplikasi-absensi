<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cs = DB::table('class_student as cs')
    ->join('school_classes as sc', 'cs.school_class_id', '=', 'sc.id')
    ->select('cs.semester_id', 'cs.school_class_id', 'sc.name', DB::raw('count(*) as student_count'))
    ->groupBy('cs.semester_id', 'cs.school_class_id', 'sc.name')
    ->orderBy('cs.semester_id')
    ->orderBy('cs.school_class_id')
    ->get();

echo "=== CLASS_STUDENT SUMMARY ===\n";
foreach ($cs as $row) {
    echo "Semester {$row->semester_id} | Class {$row->school_class_id} ({$row->name}): {$row->student_count} students\n";
}
