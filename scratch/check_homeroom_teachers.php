<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;

echo "=== ALL CLASSES AND THEIR HOMEROOM TEACHERS ===\n";
$classes = DB::table('school_classes as sc')
    ->leftJoin('teachers as t', 'sc.teacher_id', '=', 't.id')
    ->select('sc.*', 't.name as teacher_name')
    ->get();
foreach ($classes as $c) {
    echo "Class ID {$c->id}: {$c->name} (Year: {$c->academic_year_id}, Sem: {$c->semester_id}) => Homeroom: Teacher ID {$c->teacher_id} ({$c->teacher_name})\n";
}

echo "\n=== ALL TEACHERS AND THEIR CLASSES (without scope) ===\n";
$teachers = Teacher::all();
foreach ($teachers as $t) {
    $homeroomClasses = DB::table('school_classes')->where('teacher_id', $t->id)->get();
    $teachingAssignments = DB::table('teaching_assignments')->where('teacher_id', $t->id)->get();
    echo "Teacher ID {$t->id}: {$t->name} (User ID: {$t->user_id})\n";
    echo "  - Homeroom classes: " . $homeroomClasses->map(fn($c)=>"Class {$c->id} ({$c->name}, Year {$c->academic_year_id})")->join('; ') . "\n";
    echo "  - Teaching assignments: " . $teachingAssignments->map(fn($ta)=>"TA {$ta->id} (Class {$ta->school_class_id}, Subject {$ta->subject_id}, Year {$ta->academic_year_id})")->join('; ') . "\n";
}
