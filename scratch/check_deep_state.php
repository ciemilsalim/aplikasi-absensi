<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 1. ACADEMIC YEARS & SEMESTERS ===\n";
$semesters = DB::table('semesters as s')
    ->join('academic_years as ay', 's.academic_year_id', '=', 'ay.id')
    ->select('s.id as semester_id', 's.name as semester_name', 's.is_active as sem_active', 'ay.id as year_id', 'ay.name as year_name', 'ay.is_active as year_active')
    ->get();
foreach ($semesters as $s) {
    echo "Semester {$s->semester_id}: {$s->semester_name} | Year {$s->year_id}: {$s->year_name} (Year Active: {$s->year_active}, Sem Active: {$s->sem_active})\n";
}

echo "\n=== 2. SCHOOL CLASSES ===\n";
$classes = DB::table('school_classes')->get();
foreach ($classes as $c) {
    echo "Class {$c->id}: {$c->name} (Level: " . ($c->level ?? $c->grade ?? 'N/A') . ") | Year ID: {$c->academic_year_id} | Homeroom: " . ($c->homeroom_teacher_id ?? 'N/A') . "\n";
}

echo "\n=== 3. STUDENT CLASS HISTORIES ===\n";
$histories = DB::table('student_class_histories as sch')
    ->join('students as st', 'sch.student_id', '=', 'st.id')
    ->leftJoin('school_classes as sc', 'sch.school_class_id', '=', 'sc.id')
    ->select('sch.*', 'st.name as student_name', 'sc.name as class_name')
    ->get();
echo "Total history records: " . $histories->count() . "\n";
foreach ($histories as $h) {
    echo "History ID {$h->id} | Student: {$h->student_id} ({$h->student_name}) | Class: {$h->school_class_id} ({$h->class_name}) | Year: {$h->academic_year_id} | Status: {$h->status}\n";
}

echo "\n=== 4. CURRENT CLASS_STUDENT PIVOT ===\n";
$pivot = DB::table('class_student as cs')
    ->join('students as st', 'cs.student_id', '=', 'st.id')
    ->join('school_classes as sc', 'cs.school_class_id', '=', 'sc.id')
    ->select('cs.*', 'st.name as student_name', 'sc.name as class_name')
    ->get();
echo "Total class_student rows: " . $pivot->count() . "\n";
foreach ($pivot->groupBy('semester_id') as $semId => $rows) {
    echo "--- Semester {$semId} (Count: " . $rows->count() . ") ---\n";
    foreach ($rows->groupBy('school_class_id') as $classId => $cRows) {
        echo "  Class {$classId} ({$cRows->first()->class_name}): " . $cRows->count() . " students\n";
    }
}
