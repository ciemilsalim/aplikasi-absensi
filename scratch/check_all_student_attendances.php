<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ALL STUDENTS WITH ANY ATTENDANCE RECORD (DAILY OR SUBJECT) IN 2025/2026 ===\n";
$students = DB::table('students as st')
    ->leftJoin('attendances as a', 'st.id', '=', 'a.student_id')
    ->leftJoin('subject_attendances as sa', 'st.id', '=', 'sa.student_id')
    ->leftJoin('class_student as cs', function($join) {
        $join->on('st.id', '=', 'cs.student_id')->where('cs.semester_id', 1);
    })
    ->leftJoin('school_classes as sc_cs', 'cs.school_class_id', '=', 'sc_cs.id')
    ->leftJoin('school_classes as sc_curr', 'st.school_class_id', '=', 'sc_curr.id')
    ->select(
        'st.id',
        'st.name',
        'st.nis',
        'sc_curr.name as current_class',
        'sc_cs.name as class_student_sem1',
        DB::raw('count(DISTINCT a.id) as daily_att_count'),
        DB::raw('count(DISTINCT sa.id) as subject_att_count'),
        DB::raw('MIN(a.attendance_time) as min_daily_date'),
        DB::raw('MAX(a.attendance_time) as max_daily_date'),
        DB::raw('MIN(sa.created_at) as min_subject_date'),
        DB::raw('MAX(sa.created_at) as max_subject_date')
    )
    ->groupBy('st.id', 'st.name', 'st.nis', 'sc_curr.name', 'sc_cs.name')
    ->havingRaw('daily_att_count > 0 OR subject_att_count > 0')
    ->orderBy('st.id')
    ->get();

echo "Total students with attendance data: " . $students->count() . "\n";
foreach ($students as $s) {
    echo "ID: {$s->id} | {$s->name} ({$s->nis}) | CurrClass: {$s->current_class} | CS_Sem1: {$s->class_student_sem1} | DailyAtts: {$s->daily_att_count} ({$s->min_daily_date} to {$s->max_daily_date}) | SubAtts: {$s->subject_att_count} ({$s->min_subject_date} to {$s->max_subject_date})\n";
}
