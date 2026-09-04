<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== DETAILED AUDIT OF ALL STUDENTS IN DB ===\n";

$students = DB::table('students')->get();
echo "Total students in students table: " . $students->count() . "\n";

$statuses = $students->groupBy('status');
echo "\nBreakdown by status:\n";
foreach ($statuses as $status => $grp) {
    echo "  Status '" . ($status ?: 'NULL') . "': " . $grp->count() . " students\n";
}

$byCreatedAtYear = $students->groupBy(fn($s) => substr($s->created_at ?? 'UNKNOWN', 0, 4));
echo "\nBreakdown by created_at year:\n";
foreach ($byCreatedAtYear as $yr => $grp) {
    echo "  Year '{$yr}': " . $grp->count() . " students\n";
}

$byClass = $students->groupBy('school_class_id');
echo "\nBreakdown by school_class_id:\n";
foreach ($byClass as $cid => $grp) {
    $cName = DB::table('school_classes')->where('id', $cid)->value('name') ?? 'UNKNOWN';
    $cYear = DB::table('school_classes')->where('id', $cid)->value('academic_year_id') ?? 'NULL';
    echo "  Class ID {$cid} ({$cName}, Year: {$cYear}): " . $grp->count() . " students\n";
}

// Check attendances in 2025/2026 (July 2025 - June 2026)
echo "\n=== ATTENDANCES AUDIT (2025/2026) ===\n";
$att2025Students = DB::table('attendances')
    ->whereBetween('attendance_time', ['2025-07-01 00:00:00', '2026-06-30 23:59:59'])
    ->select('student_id', DB::raw('count(*) as cnt'))
    ->groupBy('student_id')
    ->get();
echo "Distinct students with daily attendance in 2025/2026: " . $att2025Students->count() . "\n";

$subAtt2025Students = DB::table('subject_attendances')
    ->whereBetween('created_at', ['2025-07-01 00:00:00', '2026-06-30 23:59:59'])
    ->select('student_id', DB::raw('count(*) as cnt'))
    ->groupBy('student_id')
    ->get();
echo "Distinct students with subject attendance in 2025/2026: " . $subAtt2025Students->count() . "\n";

// Check student_class_histories
if (Schema::hasTable('student_class_histories')) {
    echo "\n=== STUDENT CLASS HISTORIES AUDIT ===\n";
    $sch = DB::table('student_class_histories as sch')
        ->leftJoin('school_classes as sc', 'sch.school_class_id', '=', 'sc.id')
        ->select('sch.academic_year_id', 'sch.status_reason', 'sc.name as class_name', DB::raw('count(*) as cnt'))
        ->groupBy('sch.academic_year_id', 'sch.status_reason', 'sc.name')
        ->get();
    foreach ($sch as $r) {
        echo "  Year {$r->academic_year_id} | Class: " . ($r->class_name ?? 'NULL') . " | Reason: " . ($r->status_reason ?? 'NULL') . " | Count: {$r->cnt}\n";
    }
}
