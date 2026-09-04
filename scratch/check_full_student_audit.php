<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ALL STUDENTS WITH CREATION & CLASS INFO ===\n";
$students = DB::table('students as s')
    ->leftJoin('school_classes as sc', 's.school_class_id', '=', 'sc.id')
    ->select('s.id', 's.nis', 's.name', 's.school_class_id', 'sc.name as class_name', 's.status', 's.created_at')
    ->orderBy('s.id')
    ->get();

foreach ($students as $s) {
    // Check attendances
    $attCount = DB::table('attendances')->where('student_id', $s->id)->count();
    $subAttCount = DB::table('subject_attendances')->where('student_id', $s->id)->count();
    $att2025 = DB::table('attendances')->where('student_id', $s->id)->whereBetween('attendance_time', ['2025-07-01', '2026-06-30'])->count();
    $subAtt2025 = DB::table('subject_attendances')->where('student_id', $s->id)->whereBetween('created_at', ['2025-07-01', '2026-06-30'])->count();

    // Check class_student
    $csRows = DB::table('class_student')->where('student_id', $s->id)->get();
    $csSummary = [];
    foreach ($csRows as $cs) {
        $csSummary[] = "AY{$cs->academic_year_id}-S{$cs->semester_id}:Cls{$cs->school_class_id}";
    }

    echo sprintf(
        "ID: %-3d | NIS: %-12s | Name: %-32s | CurClass: %-15s (ID:%-2s) | Stat: %-10s | Created: %s | AttTotal: %d | Att2025: %d | SubAtt2025: %d | CS: [%s]\n",
        $s->id,
        $s->nis,
        substr($s->name, 0, 32),
        $s->class_name ?? 'NONE',
        $s->school_class_id ?? '-',
        $s->status ?? '-',
        $s->created_at,
        $attCount,
        $att2025,
        $subAtt2025,
        implode(', ', $csSummary)
    );
}
