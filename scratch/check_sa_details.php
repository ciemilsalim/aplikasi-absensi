<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SUBJECT_ATTENDANCES (SEM 1) DETAILS ===\n";
$saDetails = DB::table('subject_attendances as sa')
    ->leftJoin('schedules as s', 'sa.schedule_id', '=', 's.id')
    ->leftJoin('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
    ->leftJoin('students as st', 'sa.student_id', '=', 'st.id')
    ->leftJoin('subjects as sub', 'ta.subject_id', '=', 'sub.id')
    ->where('sa.semester_id', 1)
    ->select(
        'sa.schedule_id',
        's.school_class_id as sched_class_id',
        'ta.school_class_id as ta_class_id',
        'ta.subject_id',
        'sub.name as subject_name',
        DB::raw('count(DISTINCT sa.student_id) as student_count'),
        DB::raw('count(sa.id) as attendance_count')
    )
    ->groupBy('sa.schedule_id', 's.school_class_id', 'ta.school_class_id', 'ta.subject_id', 'sub.name')
    ->get();

foreach ($saDetails as $row) {
    echo "ScheduleID: {$row->schedule_id} | SchedClassID: " . ($row->sched_class_id ?? 'NULL') . " | TA_ClassID: " . ($row->ta_class_id ?? 'NULL') . " | Subject: {$row->subject_name} | Students: {$row->student_count} | Attendances: {$row->attendance_count}\n";
}

echo "\n=== STUDENTS WITH ATTENDANCES IN SEM 1 (Sample) ===\n";
$studentsSem1 = DB::table('subject_attendances as sa')
    ->join('students as st', 'sa.student_id', '=', 'st.id')
    ->where('sa.semester_id', 1)
    ->select('st.id', 'st.name', 'st.nis', 'st.school_class_id as current_class_id', DB::raw('count(*) as count'))
    ->groupBy('st.id', 'st.name', 'st.nis', 'st.school_class_id')
    ->orderBy('st.name')
    ->get();

echo "Total distinct students with subject attendances in Sem 1: " . $studentsSem1->count() . "\n";
foreach ($studentsSem1 as $s) {
    echo "StudentID: {$s->id} | Name: {$s->name} | NIS: {$s->nis} | CurrentClassID: " . ($s->current_class_id ?? 'NULL') . " | PresensiCount: {$s->count}\n";
}
