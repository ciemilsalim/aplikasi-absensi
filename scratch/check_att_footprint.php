<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Let's check students 46 to 95 and all students who have attendances in Sem 1 or 2
$attStudents = DB::table('attendances as a')
    ->whereIn('a.semester_id', [1, 2])
    ->select('a.semester_id', 'a.student_id', DB::raw('count(a.id) as count'), DB::raw('MIN(a.attendance_time) as min_t'), DB::raw('MAX(a.attendance_time) as max_t'))
    ->groupBy('a.semester_id', 'a.student_id')
    ->get();

echo "Distinct (semester_id, student_id) in attendances: " . $attStudents->count() . "\n";
foreach ($attStudents as $as) {
    $st = DB::table('students')->where('id', $as->student_id)->first();
    $hist = DB::table('student_class_histories')->where('student_id', $as->student_id)->get();
    $histClasses = $hist->pluck('school_class_id')->join(',');
    
    // Check if there are subject attendances
    $sa = DB::table('subject_attendances as s')
        ->leftJoin('schedules as sc', 's.schedule_id', '=', 'sc.id')
        ->leftJoin('teaching_assignments as ta', 'sc.teaching_assignment_id', '=', 'ta.id')
        ->where('s.student_id', $as->student_id)
        ->where('s.semester_id', $as->semester_id)
        ->select(DB::raw('COALESCE(sc.school_class_id, ta.school_class_id) as class_id'))
        ->groupBy('class_id')
        ->pluck('class_id')
        ->join(',');
    
    echo "Sem {$as->semester_id} | Student {$as->student_id}: {$st->name} ({$st->nis}) | direct_c: {$st->school_class_id} | hist_c: {$histClasses} | sa_c: {$sa} | atts: {$as->count} ({$as->min_t} to {$as->max_t})\n";
}
