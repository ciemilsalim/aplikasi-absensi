<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Detect from Subject Attendances in 2025/2026 (Genap & Ganjil)
echo "=== 1. DETECTING CLASS FROM SUBJECT ATTENDANCES ===\n";
$fromSubject = DB::table('subject_attendances as sa')
    ->leftJoin('schedules as s', 'sa.schedule_id', '=', 's.id')
    ->leftJoin('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
    ->join('students as st', 'sa.student_id', '=', 'st.id')
    ->select(
        'sa.semester_id',
        'sa.student_id',
        'st.name as student_name',
        'st.nis',
        DB::raw('COALESCE(s.school_class_id, ta.school_class_id) as detected_class_id'),
        DB::raw('count(*) as attendance_count')
    )
    ->groupBy('sa.semester_id', 'sa.student_id', 'st.name', 'st.nis', 'detected_class_id')
    ->get();

foreach ($fromSubject as $row) {
    echo "Semester {$row->semester_id} | Student ID: {$row->student_id} ({$row->student_name} - {$row->nis}) => ClassID: {$row->detected_class_id} (Presensi: {$row->attendance_count})\n";
}

// 2. Detect from Daily Attendances in 2025/2026 (Genap & Ganjil)
echo "\n=== 2. DETECTING FROM DAILY ATTENDANCES ===\n";
$fromDaily = DB::table('attendances as a')
    ->join('students as st', 'a.student_id', '=', 'st.id')
    ->leftJoin('student_class_histories as sch', 'st.id', '=', 'sch.student_id')
    ->select(
        'a.semester_id',
        'a.student_id',
        'st.name as student_name',
        'st.nis',
        'st.school_class_id as current_class_id',
        'sch.school_class_id as history_class_id',
        DB::raw('count(a.id) as attendance_count'),
        DB::raw('MIN(a.attendance_time) as first_att'),
        DB::raw('MAX(a.attendance_time) as last_att')
    )
    ->whereIn('a.semester_id', [1, 2])
    ->groupBy('a.semester_id', 'a.student_id', 'st.name', 'st.nis', 'st.school_class_id', 'sch.school_class_id')
    ->get();

echo "Total daily attendance student groups in 2025/2026: " . $fromDaily->count() . "\n";
foreach ($fromDaily->take(30) as $row) {
    echo "Semester {$row->semester_id} | Student ID: {$row->student_id} ({$row->student_name} - {$row->nis}) | CurrClass: {$row->current_class_id} | HistoryClass: {$row->history_class_id} | Count: {$row->attendance_count} ({$row->first_att} to {$row->last_att})\n";
}
