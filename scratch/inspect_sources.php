<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== EXPLORING ALL DATA SOURCES FOR STUDENTS & CLASSES ===\n";

$year2025 = AcademicYear::where('name', 'like', '%2025%')->first() ?? AcademicYear::orderBy('id')->first();
$year2026 = AcademicYear::where('name', 'like', '%2026%')->first() ?? AcademicYear::orderBy('id', 'desc')->first();

echo "Year 2025: ID {$year2025->id} ({$year2025->name})\n";
echo "Year 2026: ID {$year2026->id} ({$year2026->name})\n";

// 1. Check all classes in DB
$allClasses = DB::table('school_classes')->get();
echo "\nTotal School Classes in DB: " . $allClasses->count() . "\n";
foreach ($allClasses as $c) {
    echo "Class ID {$c->id}: {$c->name} (Year: {$c->academic_year_id}, Sem: {$c->semester_id}, Teacher: {$c->teacher_id})\n";
}

// 2. Check student_class_histories if exists
if (Schema::hasTable('student_class_histories')) {
    $sch = DB::table('student_class_histories')->get();
    echo "\nstudent_class_histories count: " . $sch->count() . "\n";
    foreach ($sch->take(10) as $r) {
        echo "SCH: Student {$r->student_id} => Class {$r->school_class_id} (Year: {$r->academic_year_id})\n";
    }
} else {
    echo "\nstudent_class_histories does not exist.\n";
}

// 3. Check subject_attendances
if (Schema::hasTable('subject_attendances')) {
    $sa = DB::table('subject_attendances as sa')
        ->leftJoin('schedules as s', 'sa.schedule_id', '=', 's.id')
        ->leftJoin('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
        ->select(
            DB::raw('COALESCE(s.school_class_id, ta.school_class_id) as class_id'),
            DB::raw('count(DISTINCT sa.student_id) as student_count'),
            DB::raw('count(*) as total_records')
        )
        ->groupBy('class_id')
        ->get();
    echo "\nSubject Attendances per class:\n";
    foreach ($sa as $r) {
        echo "Class ID {$r->class_id}: {$r->student_count} distinct students ({$r->total_records} attendance records)\n";
    }
}

// 4. Check all students in DB
$students = DB::table('students')->get();
echo "\nTotal Students in DB: " . $students->count() . "\n";
$byClass = $students->groupBy('school_class_id');
foreach ($byClass as $cid => $group) {
    echo "Current school_class_id '{$cid}': " . $group->count() . " students\n";
}
