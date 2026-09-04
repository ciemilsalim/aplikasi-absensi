<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ACADEMIC YEARS ===\n";
foreach (DB::table('academic_years')->get() as $ay) {
    echo "ID: {$ay->id} | Name: {$ay->name} | Status: " . ($ay->status ?? $ay->is_active ?? 'N/A') . "\n";
}

echo "\n=== SEMESTERS ===\n";
foreach (DB::table('semesters')->get() as $s) {
    echo "ID: {$s->id} | AY_ID: {$s->academic_year_id} | Name: {$s->name} | IsActive: " . ($s->is_active ?? 'N/A') . "\n";
}

echo "\n=== SCHOOL CLASSES ===\n";
foreach (DB::table('school_classes')->get() as $sc) {
    echo "ID: {$sc->id} | Name: {$sc->name} | AY_ID: " . ($sc->academic_year_id ?? 'NULL') . " | SemID: " . ($sc->semester_id ?? 'NULL') . " | TeacherID: " . ($sc->teacher_id ?? 'NULL') . "\n";
}

echo "\n=== STUDENTS COUNT BY STATUS / CLASS ===\n";
$students = DB::table('students')->select('status', DB::raw('count(*) as c'))->groupBy('status')->get();
foreach ($students as $st) {
    echo "Status: " . ($st->status ?? 'NULL') . " -> Count: {$st->c}\n";
}

echo "\n=== CLASS_STUDENT SUMMARY ===\n";
$cs = DB::table('class_student')
    ->select('academic_year_id', 'semester_id', 'school_class_id', DB::raw('count(*) as count'))
    ->groupBy('academic_year_id', 'semester_id', 'school_class_id')
    ->get();
foreach ($cs as $row) {
    $className = DB::table('school_classes')->where('id', $row->school_class_id)->value('name');
    echo "AY: {$row->academic_year_id} | Sem: {$row->semester_id} | ClassID: {$row->school_class_id} ({$className}) -> Count: {$row->count}\n";
}
