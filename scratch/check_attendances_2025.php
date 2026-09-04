<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ATTENDANCES IN 2025/2026 (July 2025 - June 2026) ===\n";

$studentsWithAtt = DB::table('attendances as a')
    ->join('students as s', 'a.student_id', '=', 's.id')
    ->whereBetween('a.attendance_time', ['2025-07-01', '2026-06-30'])
    ->select('s.id', 's.nis', 's.name', DB::raw('count(*) as att_count'), DB::raw('min(a.attendance_time) as first_att'), DB::raw('max(a.attendance_time) as last_att'))
    ->groupBy('s.id', 's.nis', 's.name')
    ->orderBy('s.nis')
    ->get();

echo "Students with attendances in 2025/2026 (Count: " . $studentsWithAtt->count() . "):\n";
foreach ($studentsWithAtt as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | AttCount: {$s->att_count} | From: {$s->first_att} To: {$s->last_att}\n";
}

echo "\n=== SUBJECT ATTENDANCES IN 2025/2026 ===\n";
$studentsWithSubAtt = DB::table('subject_attendances as sa')
    ->join('students as s', 'sa.student_id', '=', 's.id')
    ->whereBetween('sa.created_at', ['2025-07-01', '2026-06-30'])
    ->select('s.id', 's.nis', 's.name', DB::raw('count(*) as sub_count'), DB::raw('min(sa.created_at) as first_att'), DB::raw('max(sa.created_at) as last_att'))
    ->groupBy('s.id', 's.nis', 's.name')
    ->orderBy('s.nis')
    ->get();

echo "Students with subject attendances in 2025/2026 (Count: " . $studentsWithSubAtt->count() . "):\n";
foreach ($studentsWithSubAtt as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | SubAttCount: {$s->sub_count} | From: {$s->first_att} To: {$s->last_att}\n";
}
