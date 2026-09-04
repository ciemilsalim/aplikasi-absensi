<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLASS_STUDENT ROWS FOR ACADEMIC YEAR 1 (2025/2026) ===\n";

$rows = DB::table('class_student as cs')
    ->join('students as st', 'cs.student_id', '=', 'st.id')
    ->join('school_classes as sc', 'cs.school_class_id', '=', 'sc.id')
    ->where('cs.academic_year_id', 1)
    ->select('cs.semester_id', 'sc.name as class_name', 'st.id as student_id', 'st.nis', 'st.name as student_name', 'st.created_at as student_created_at')
    ->orderBy('cs.semester_id')
    ->orderBy('sc.name')
    ->orderBy('st.nis')
    ->get();

echo "Total class_student rows for AY 1: " . $rows->count() . "\n\n";

$bySemClass = [];
foreach ($rows as $r) {
    $bySemClass["Sem {$r->semester_id} - {$r->class_name}"][] = $r;
}

foreach ($bySemClass as $key => $list) {
    echo "=== {$key} (Count: " . count($list) . ") ===\n";
    foreach ($list as $s) {
        $warning = ($s->student_created_at >= '2026-07-01') ? " [!!! NEW 2026/2027 STUDENT !!!]" : "";
        echo "   StID: {$s->student_id} | NIS: {$s->nis} | Name: {$s->student_name} | Created: {$s->student_created_at}{$warning}\n";
    }
    echo "\n";
}
