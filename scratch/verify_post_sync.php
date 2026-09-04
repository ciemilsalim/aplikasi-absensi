<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== POST-SYNC VERIFICATION ===\n\n";

// 1. Check Kelas 9A students in Semester 2 (2025/2026 Ganjil)
$csSem2 = DB::table('class_student as cs')
    ->join('students as st', 'cs.student_id', '=', 'st.id')
    ->where('cs.school_class_id', 2)
    ->where('cs.semester_id', 2)
    ->select('st.id', 'st.nis', 'st.name', 'st.created_at')
    ->orderBy('st.nis')
    ->get();

echo "Kelas 9A - TA 2025/2026 Ganjil (Semester 2) Students Count: " . $csSem2->count() . "\n";
foreach ($csSem2 as $s) {
    echo "  - NIS: {$s->nis} | {$s->name} (Created: {$s->created_at})\n";
}

// 2. Check Kelas 9A students in Semester 1 (2025/2026 Genap)
$csSem1 = DB::table('class_student as cs')
    ->join('students as st', 'cs.student_id', '=', 'st.id')
    ->where('cs.school_class_id', 2)
    ->where('cs.semester_id', 1)
    ->select('st.id', 'st.nis', 'st.name', 'st.created_at')
    ->orderBy('st.nis')
    ->get();

echo "\nKelas 9A - TA 2025/2026 Genap (Semester 1) Students Count: " . $csSem1->count() . "\n";

// 3. Check Semester 3 (2026/2027) - Must NOT contain any 2025/2026 AY 1 rows!
$csSem3Bogus = DB::table('class_student')
    ->where('academic_year_id', 1)
    ->where('semester_id', 3)
    ->count();

echo "\nBogus class_student rows in AY 1 for Semester 3: {$csSem3Bogus} (MUST BE 0)\n";

// 4. Check whether any 2026/2027 new student is in AY 1
$csNew2026InAy1 = DB::table('class_student as cs')
    ->join('students as st', 'cs.student_id', '=', 'st.id')
    ->where('cs.academic_year_id', 1)
    ->where(function($q) {
        $q->where('st.created_at', '>=', '2026-07-01')
          ->orWhere('st.nis', 'like', 'TEST%');
    })
    ->count();

echo "2026/2027 newly enrolled students accidentally in AY 1: {$csNew2026InAy1} (MUST BE 0)\n";

// 5. Check 2026/2027 intact classes
$classes2026 = DB::table('school_classes')->where('academic_year_id', 2)->get();
echo "\nTA 2026/2027 Classes Count: " . $classes2026->count() . "\n";
foreach ($classes2026 as $c) {
    $stCount = DB::table('class_student')->where('school_class_id', $c->id)->where('academic_year_id', 2)->count();
    echo "  - Class ID {$c->id}: {$c->name} (Students in AY 2: {$stCount})\n";
}
