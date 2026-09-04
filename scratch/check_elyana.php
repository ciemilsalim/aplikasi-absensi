<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;

echo "=== 1. FIND TEACHER ELYANA ===\n";
$teacher = Teacher::where('name', 'like', '%Elyana%')->first();
if ($teacher) {
    echo "Teacher found: ID {$teacher->id} | Name: {$teacher->name} | User ID: {$teacher->user_id}\n";
    $user = User::find($teacher->user_id);
    if ($user) {
        echo "User found: ID {$user->id} | Name: {$user->name} | Email: {$user->email} | Role: {$user->role}\n";
    }
} else {
    echo "Teacher Elyana not found by name!\n";
}

echo "\n=== 2. SCHOOL CLASSES WHERE TEACHER_ID = {$teacher->id} ===\n";
$classes = DB::table('school_classes')->where('teacher_id', $teacher->id)->get();
foreach ($classes as $c) {
    echo json_encode($c) . "\n";
}

echo "\n=== 3. ALL SCHOOL CLASSES IN DB ===\n";
$allClasses = DB::table('school_classes')->get();
foreach ($allClasses as $c) {
    echo "Class {$c->id}: {$c->name} | teacher_id: {$c->teacher_id} | year: {$c->academic_year_id} | sem: {$c->semester_id} | deleted: {$c->deleted_at}\n";
}

echo "\n=== 4. TEACHING ASSIGNMENTS FOR TEACHER_ID = {$teacher->id} ===\n";
$ta = DB::table('teaching_assignments')->where('teacher_id', $teacher->id)->get();
foreach ($ta as $r) {
    echo json_encode($r) . "\n";
}
