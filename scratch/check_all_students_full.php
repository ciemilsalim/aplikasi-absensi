<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ALL STUDENTS SUMMARY ===\n";
$students = DB::table('students as st')
    ->leftJoin('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
    ->select('st.id', 'st.name', 'st.nis', 'st.school_class_id', 'sc.name as class_name', 'sc.academic_year_id as class_year_id')
    ->orderBy('st.id')
    ->get();

echo "Total students: " . $students->count() . "\n";
foreach ($students as $st) {
    // Check attendances in sem 1, 2, 3
    $attCount1 = DB::table('attendances')->where('student_id', $st->id)->where('semester_id', 1)->count();
    $attCount2 = DB::table('attendances')->where('student_id', $st->id)->where('semester_id', 2)->count();
    $attCount3 = DB::table('attendances')->where('student_id', $st->id)->where('semester_id', 3)->count();
    $sattCount1 = DB::table('subject_attendances')->where('student_id', $st->id)->where('semester_id', 1)->count();
    $sattCount2 = DB::table('subject_attendances')->where('student_id', $st->id)->where('semester_id', 2)->count();
    $sattCount3 = DB::table('subject_attendances')->where('student_id', $st->id)->where('semester_id', 3)->count();
    
    // Check history
    $histories = DB::table('student_class_histories')->where('student_id', $st->id)->get();
    $histStr = $histories->map(fn($h)=>"c{$h->school_class_id}_y{$h->academic_year_id}")->join(', ');
    
    // Check pivot
    $pivots = DB::table('class_student')->where('student_id', $st->id)->get();
    $pivStr = $pivots->map(fn($p)=>"c{$p->school_class_id}_s{$p->semester_id}")->join(', ');

    echo "ID {$st->id} | {$st->name} ({$st->nis}) | direct_c: {$st->school_class_id} ({$st->class_name}) | Att[s1:{$attCount1},s2:{$attCount2},s3:{$attCount3}] | SAtt[s1:{$sattCount1},s2:{$sattCount2},s3:{$sattCount3}] | Hist: [{$histStr}] | Piv: [{$pivStr}]\n";
}
