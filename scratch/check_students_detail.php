<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== STUDENTS COLUMNS ===\n";
$cols = DB::getSchemaBuilder()->getColumnListing('students');
print_r($cols);

echo "\n=== ALL STUDENTS SAMPLE (Total: " . DB::table('students')->count() . ") ===\n";
$students = DB::table('students')->orderBy('id')->get();
foreach ($students->take(30) as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | ClassID: " . ($s->school_class_id ?? 'NULL') . " | Status: " . ($s->status ?? 'NULL') . " | Created: {$s->created_at}\n";
}

echo "\n=== STUDENTS WITH school_class_id = 2 (Kelas 9A in 2025/2026) ===\n";
$s9a = DB::table('students')->where('school_class_id', 2)->get();
foreach ($s9a as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | Created: {$s->created_at}\n";
}

echo "\n=== STUDENTS IN class_student FOR CLASS 2 ===\n";
$cs9a = DB::table('class_student as cs')
    ->join('students as st', 'cs.student_id', '=', 'st.id')
    ->where('cs.school_class_id', 2)
    ->select('st.id', 'st.nis', 'st.name', 'st.school_class_id as current_class_id', 'st.created_at', 'cs.academic_year_id', 'cs.semester_id')
    ->get();
foreach ($cs9a as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | CurClass: {$s->current_class_id} | Created: {$s->created_at} | AY: {$s->academic_year_id} | Sem: {$s->semester_id}\n";
}

echo "\n=== CHECK ATTENDANCES 2025/2026 ===\n";
$att9a = DB::table('attendances as a')
    ->join('students as st', 'a.student_id', '=', 'st.id')
    ->whereBetween('a.attendance_time', ['2025-07-01', '2026-06-30'])
    ->select('st.id', 'st.nis', 'st.name', 'st.school_class_id', DB::raw('count(*) as c'))
    ->groupBy('st.id', 'st.nis', 'st.name', 'st.school_class_id')
    ->get();
echo "Total distinct students with attendance in 2025/2026: " . $att9a->count() . "\n";
foreach ($att9a->take(20) as $a) {
    echo "StID: {$a->id} | NIS: {$a->nis} | Name: {$a->name} | ClassID: {$a->school_class_id} | Presensi: {$a->c}\n";
}

echo "\n=== CHECK SUBJECT ATTENDANCES 2025/2026 ===\n";
$satt = DB::table('subject_attendances as sa')
    ->join('students as st', 'sa.student_id', '=', 'st.id')
    ->whereBetween('sa.created_at', ['2025-07-01', '2026-06-30'])
    ->select('st.id', 'st.nis', 'st.name', DB::raw('count(*) as c'))
    ->groupBy('st.id', 'st.nis', 'st.name')
    ->get();
echo "Total distinct students in subject_attendances in 2025/2026: " . $satt->count() . "\n";
