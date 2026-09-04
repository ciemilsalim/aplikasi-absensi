<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 1. ALL SCHOOL CLASSES IN DB ===\n";
$classes = DB::table('school_classes')->get();
foreach ($classes as $c) {
    echo "ID: {$c->id} | Name: {$c->name} | SemesterID: " . ($c->semester_id ?? 'NULL') . " | AcademicYearID: " . ($c->academic_year_id ?? 'NULL') . " | TeacherID: " . ($c->teacher_id ?? 'NULL') . " | DeletedAt: " . ($c->deleted_at ?? 'NULL') . "\n";
}

echo "\n=== 2. ALL CLASS_STUDENT IN DB ===\n";
$cs = DB::table('class_student')
    ->select('semester_id', 'academic_year_id', 'school_class_id', DB::raw('count(*) as count'))
    ->groupBy('semester_id', 'academic_year_id', 'school_class_id')
    ->get();
foreach ($cs as $row) {
    echo "SemesterID: " . ($row->semester_id ?? 'NULL') . " | YearID: " . ($row->academic_year_id ?? 'NULL') . " | ClassID: {$row->school_class_id} | Total Students: {$row->count}\n";
}

echo "\n=== 3. STUDENT CLASS HISTORIES TABLE ===\n";
try {
    $sch = DB::table('student_class_histories')->get();
    echo "Total rows in student_class_histories: " . $sch->count() . "\n";
    foreach ($sch->take(10) as $row) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error reading student_class_histories: " . $e->getMessage() . "\n";
}

echo "\n=== 4. UNIQUE STUDENTS IN SUBJECT_ATTENDANCES (2025/2026 Genap - Sem 1) ===\n";
$saStudents = DB::table('subject_attendances as sa')
    ->join('schedules as s', 'sa.schedule_id', '=', 's.id')
    ->where('sa.semester_id', 1)
    ->select('s.school_class_id', DB::raw('count(DISTINCT sa.student_id) as student_count'), DB::raw('count(sa.id) as attendance_count'))
    ->groupBy('s.school_class_id')
    ->get();
foreach ($saStudents as $row) {
    echo "ClassID from Schedule: {$row->school_class_id} | Unique Students: {$row->student_count} | Total Attendances: {$row->attendance_count}\n";
}

echo "\n=== 5. UNIQUE STUDENTS IN DAILY ATTENDANCES (2025/2026 Genap & Ganjil) ===\n";
$daStudents = DB::table('attendances as a')
    ->join('students as st', 'a.student_id', '=', 'st.id')
    ->whereIn('a.semester_id', [1, 2])
    ->select('a.semester_id', 'st.school_class_id as current_class_id', DB::raw('count(DISTINCT a.student_id) as student_count'), DB::raw('count(a.id) as attendance_count'))
    ->groupBy('a.semester_id', 'st.school_class_id')
    ->get();
foreach ($daStudents as $row) {
    echo "SemesterID: {$row->semester_id} | Current Student ClassID: " . ($row->current_class_id ?? 'NULL') . " | Unique Students: {$row->student_count} | Total Attendances: {$row->attendance_count}\n";
}
