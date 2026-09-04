<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TESTING SMART RECONSTRUCTION ALGORITHM ===\n";

$year2025 = AcademicYear::where('name', 'like', '%2025%')->first() ?? AcademicYear::orderBy('id')->first();
$year2026 = AcademicYear::where('name', 'like', '%2026%')->first() ?? AcademicYear::orderBy('id', 'desc')->first();

$semesters2025 = Semester::where('academic_year_id', $year2025->id)->get();

// Standard 13 classes
$targetClassNames = [
    'Kelas 7A' => 1, 'Kelas 7B' => 1, 'Kelas 7C' => 1, 'Kelas 7D' => 1, 'Kelas 7E' => 1,
    'Kelas 8A' => 2, 'Kelas 8B' => 2, 'Kelas 8C' => 2, 'Kelas 8D' => 2,
    'Kelas 9A' => 3, 'Kelas 9B' => 3, 'Kelas 9C' => 3, 'Kelas 9D' => 3,
];

// Helper to normalize class name (e.g. '7A' -> 'Kelas 7A')
function normalizeClassName($name) {
    $clean = trim($name);
    if (!str_starts_with(strtolower($clean), 'kelas') && preg_match('/^[789][A-Ea-e]/', $clean)) {
        return 'Kelas ' . strtoupper($clean);
    }
    return $clean;
}

// 1. Map existing 2025 classes in DB
$existing2025Classes = DB::table('school_classes')
    ->where('academic_year_id', $year2025->id)
    ->get()
    ->keyBy(fn($c) => normalizeClassName($c->name));

echo "Existing 2025 classes in DB: " . $existing2025Classes->keys()->join(', ') . "\n";

// 2. Discover student assignments
$studentClassMap = []; // student_id => class_name

// Source 1: student_class_histories
if (Schema::hasTable('student_class_histories')) {
    $schRows = DB::table('student_class_histories as sch')
        ->join('school_classes as sc', 'sch.school_class_id', '=', 'sc.id')
        ->where(function($q) use ($year2025) {
            $q->where('sch.academic_year_id', $year2025->id)
              ->orWhere('sc.academic_year_id', $year2025->id);
        })
        ->select('sch.student_id', 'sc.name as class_name')
        ->get();
    
    foreach ($schRows as $r) {
        $norm = normalizeClassName($r->class_name);
        $studentClassMap[$r->student_id] = $norm;
    }
    echo "Found " . count($studentClassMap) . " mappings from student_class_histories.\n";
}

// Source 2: subject_attendances in 2025/2026
if (Schema::hasTable('subject_attendances')) {
    $saRows = DB::table('subject_attendances as sa')
        ->leftJoin('schedules as s', 'sa.schedule_id', '=', 's.id')
        ->leftJoin('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
        ->leftJoin('school_classes as sc', DB::raw('COALESCE(s.school_class_id, ta.school_class_id)'), '=', 'sc.id')
        ->where(function($q) use ($semesters2025) {
            $q->whereIn('sa.semester_id', $semesters2025->pluck('id'))
              ->orWhereBetween('sa.created_at', ['2025-07-01', '2026-06-30']);
        })
        ->whereNotNull('sc.name')
        ->select('sa.student_id', 'sc.name as class_name', DB::raw('count(*) as cnt'))
        ->groupBy('sa.student_id', 'sc.name')
        ->orderByDesc('cnt')
        ->get();

    $addedSA = 0;
    foreach ($saRows as $r) {
        if (!isset($studentClassMap[$r->student_id])) {
            $studentClassMap[$r->student_id] = normalizeClassName($r->class_name);
            $addedSA++;
        }
    }
    echo "Found {$addedSA} new mappings from subject_attendances.\n";
}

// Source 3: Direct students.school_class_id if class was 2025/2026
$studentsDirect = DB::table('students as st')
    ->join('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
    ->where(function($q) use ($year2025) {
        $q->where('sc.academic_year_id', $year2025->id)
          ->orWhere('sc.created_at', '<', '2026-07-01');
    })
    ->select('st.id as student_id', 'sc.name as class_name')
    ->get();

$addedDirect = 0;
foreach ($studentsDirect as $r) {
    if (!isset($studentClassMap[$r->student_id])) {
        $studentClassMap[$r->student_id] = normalizeClassName($r->class_name);
        $addedDirect++;
    }
}
echo "Found {$addedDirect} new mappings from direct student 2025 school_class_id.\n";

// Source 4: Grade promotion / historical inference for active 2026/2027 students
// e.g. If current class in 2026/2027 is 'Kelas 8A', previous 2025 class was 'Kelas 7A'
$allCurrentStudents = DB::table('students as st')
    ->leftJoin('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
    ->select('st.id as student_id', 'st.name', 'st.nis', 'sc.name as current_class_name')
    ->get();

$addedInferred = 0;
foreach ($allCurrentStudents as $st) {
    if (isset($studentClassMap[$st->student_id])) {
        continue;
    }

    if ($st->current_class_name) {
        $currNorm = normalizeClassName($st->current_class_name);
        // Regex check: Kelas 8[A-E] -> Kelas 7[A-E]
        if (preg_match('/Kelas 8([A-Ea-e])/i', $currNorm, $m)) {
            $prevClass = 'Kelas 7' . strtoupper($m[1]);
            $studentClassMap[$st->student_id] = $prevClass;
            $addedInferred++;
        }
        // Regex check: Kelas 9[A-E] -> Kelas 8[A-E]
        elseif (preg_match('/Kelas 9([A-Ea-e])/i', $currNorm, $m)) {
            $prevClass = 'Kelas 8' . strtoupper($m[1]);
            $studentClassMap[$st->student_id] = $prevClass;
            $addedInferred++;
        }
    }
}
echo "Found {$addedInferred} new mappings from grade promotion inference.\n";

// Group total discovered students by class
$summary = [];
foreach ($studentClassMap as $sid => $cname) {
    $summary[$cname] = ($summary[$cname] ?? 0) + 1;
}

echo "\n=== SUMMARY OF STUDENTS MAPPED TO 2025/2026 CLASSES ===\n";
foreach ($summary as $cname => $count) {
    echo "{$cname}: {$count} students\n";
}
echo "Total mapped students: " . count($studentClassMap) . "\n";
