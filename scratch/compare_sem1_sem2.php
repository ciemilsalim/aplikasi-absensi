<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check which students are in Semester 1 class_student
$sem1Students = DB::table('class_student')->where('semester_id', 1)->get();
echo "Total in Sem 1 class_student: " . $sem1Students->count() . "\n";

// Check distinct students with attendances in Sem 2
$sem2AttStudentIds = DB::table('attendances')->where('semester_id', 2)->pluck('student_id')->unique();
echo "Total distinct students with attendance in Sem 2: " . $sem2AttStudentIds->count() . "\n";

$notInSem1 = $sem2AttStudentIds->diff($sem1Students->pluck('student_id'));
echo "Students in Sem 2 attendances but NOT in Sem 1 class_student: " . $notInSem1->count() . " (IDs: " . $notInSem1->join(', ') . ")\n";

$inSem1 = $sem2AttStudentIds->intersect($sem1Students->pluck('student_id'));
echo "Students in both: " . $inSem1->count() . "\n";
