<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLASS_STUDENT FOR 2026/2027 (academic_year_id = 2 OR semester_id = 3) ===\n";

$rows = DB::table('class_student as cs')
    ->leftJoin('students as st', 'cs.student_id', '=', 'st.id')
    ->leftJoin('school_classes as sc', 'cs.school_class_id', '=', 'sc.id')
    ->where('cs.academic_year_id', 2)
    ->orWhere('cs.semester_id', 3)
    ->select('cs.academic_year_id', 'cs.semester_id', 'cs.school_class_id', 'sc.name as class_name', 'st.id as student_id', 'st.nis', 'st.name as student_name', 'st.created_at')
    ->get();

echo "Total rows: " . $rows->count() . "\n";
foreach ($rows as $r) {
    echo "AY: {$r->academic_year_id} | Sem: {$r->semester_id} | ClassID: {$r->school_class_id} ({$r->class_name}) | StID: {$r->student_id} | NIS: {$r->nis} | Name: {$r->student_name} | Created: {$r->created_at}\n";
}
