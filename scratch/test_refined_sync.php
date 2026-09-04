<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== RUNNING SIMULATION OF REFINED SYNC LOGIC ===\n\n";

$year2025 = AcademicYear::where('name', 'like', '%2025%')->orWhere('id', 1)->first();
$year2026 = AcademicYear::where('name', 'like', '%2026%')
    ->where(function($q) use ($year2025) {
        if ($year2025) {
            $q->where('id', '!=', $year2025->id);
        }
    })
    ->first();

$lockedYearId = $year2026?->id;

$semesters2025 = Semester::where('academic_year_id', $year2025->id)
    ->where(function($q) use ($lockedYearId) {
        if ($lockedYearId) {
            $q->where('academic_year_id', '!=', $lockedYearId);
        }
        $q->where(function($sub) {
            $sub->where('created_at', '<', '2026-07-01')
                ->orWhere('name', 'like', '%2025%');
        });
    })
    ->get();

echo "2025/2026 Semesters found: " . $semesters2025->pluck('id')->join(', ') . " (" . $semesters2025->pluck('name')->join(', ') . ")\n";

// 2026/2027 Locked Student IDs
$locked2026StudentIds = DB::table('students')
    ->where(function($q) use ($lockedYearId) {
        $q->where('created_at', '>=', '2026-07-01')
          ->orWhere('nis', 'like', 'TEST%');
        if ($lockedYearId) {
            $q->orWhereIn('school_class_id', function($sub) use ($lockedYearId) {
                $sub->select('id')->from('school_classes')->where('academic_year_id', $lockedYearId);
            });
        }
    })
    ->pluck('id')
    ->toArray();

echo "Locked 2026/2027 student count: " . count($locked2026StudentIds) . "\n\n";

$standardClasses = [
    'Kelas 7A' => ['level_id' => 1, 'aliases' => ['7A', '7 A', 'VII A', 'VII-A']],
    'Kelas 7B' => ['level_id' => 1, 'aliases' => ['7B', '7 B', 'VII B', 'VII-B']],
    'Kelas 7C' => ['level_id' => 1, 'aliases' => ['7C', '7 C', 'VII C', 'VII-C']],
    'Kelas 7D' => ['level_id' => 1, 'aliases' => ['7D', '7 D', 'VII D', 'VII-D']],
    'Kelas 7E' => ['level_id' => 1, 'aliases' => ['7E', '7 E', 'VII E', 'VII-E']],
    'Kelas 8A' => ['level_id' => 2, 'aliases' => ['8A', '8 A', 'VIII A', 'VIII-A']],
    'Kelas 8B' => ['level_id' => 2, 'aliases' => ['8B', '8 B', 'VIII B', 'VIII-B']],
    'Kelas 8C' => ['level_id' => 2, 'aliases' => ['8C', '8 C', 'VIII C', 'VIII-C']],
    'Kelas 8D' => ['level_id' => 2, 'aliases' => ['8D', '8 D', 'VIII D', 'VIII-D']],
    'Kelas 9A' => ['level_id' => 3, 'aliases' => ['9A', '9 A', 'IX A', 'IX-A']],
    'Kelas 9B' => ['level_id' => 3, 'aliases' => ['9B', '9 B', 'IX B', 'IX-B']],
    'Kelas 9C' => ['level_id' => 3, 'aliases' => ['9C', '9 C', 'IX C', 'IX-C']],
    'Kelas 9D' => ['level_id' => 3, 'aliases' => ['9D', '9 D', 'IX D', 'IX-D']],
];

$normalizeName = function($name) use ($standardClasses) {
    $trimmed = trim($name);
    foreach ($standardClasses as $stdName => $meta) {
        if (strcasecmp($trimmed, $stdName) === 0) return $stdName;
        foreach ($meta['aliases'] as $alias) {
            if (strcasecmp($trimmed, $alias) === 0) return $stdName;
        }
    }
    if (preg_match('/^([789])\s*([A-Ea-e])/i', $trimmed, $m)) {
        return 'Kelas ' . $m[1] . strtoupper($m[2]);
    }
    return $trimmed;
};

$studentClassMap = []; // student_id => normalized_class_name

// 1. Cohort Kelas 9A (NIS 1200..1228) - 29 siswa definitif kelas 9A TA 2025/2026
$s9aCohort = DB::table('students')
    ->whereBetween('nis', ['1200', '1228'])
    ->whereNotIn('id', $locked2026StudentIds)
    ->pluck('id');

foreach ($s9aCohort as $sid) {
    $studentClassMap[$sid] = 'Kelas 9A';
}

// 2. Sumber: Presensi 2025/2026 & Mapel Presensi
if (Schema::hasTable('subject_attendances')) {
    $saRows = DB::table('subject_attendances as sa')
        ->leftJoin('schedules as s', 'sa.schedule_id', '=', 's.id')
        ->leftJoin('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
        ->leftJoin('school_classes as sc', DB::raw('COALESCE(s.school_class_id, ta.school_class_id)'), '=', 'sc.id')
        ->where(function($q) use ($semesters2025) {
            $q->whereIn('sa.semester_id', $semesters2025->pluck('id'))
              ->orWhereBetween('sa.created_at', ['2025-07-01', '2026-06-30']);
        })
        ->whereNotIn('sa.student_id', $locked2026StudentIds)
        ->whereNotNull('sc.name')
        ->select('sa.student_id', 'sc.name as class_name', DB::raw('count(*) as cnt'))
        ->groupBy('sa.student_id', 'sc.name')
        ->orderByDesc('cnt')
        ->get();

    foreach ($saRows as $r) {
        if (!isset($studentClassMap[$r->student_id])) {
            $norm = $normalizeName($r->class_name);
            if (isset($standardClasses[$norm])) {
                $studentClassMap[$r->student_id] = $norm;
            }
        }
    }
}

// 3. Sumber: Siswa kelas 2025/2026 (school_classes dengan academic_year_id = 1)
$directStudents = DB::table('students as st')
    ->join('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
    ->where('sc.academic_year_id', $year2025->id)
    ->whereNotIn('st.id', $locked2026StudentIds)
    ->select('st.id as student_id', 'sc.name as class_name')
    ->get();

foreach ($directStudents as $r) {
    if (!isset($studentClassMap[$r->student_id])) {
        $norm = $normalizeName($r->class_name);
        if (isset($standardClasses[$norm])) {
            $studentClassMap[$r->student_id] = $norm;
        }
    }
}

// 4. Sumber: Siswa 2024xxxx -> Grade 8 (8A, 8B, 8C, 8D)
$s2024 = DB::table('students')
    ->where('nis', 'like', '2024%')
    ->whereNotIn('id', $locked2026StudentIds)
    ->whereNotIn('id', array_keys($studentClassMap))
    ->get();

$c8List = ['Kelas 8A', 'Kelas 8B', 'Kelas 8C', 'Kelas 8D'];
$idx8 = 0;
foreach ($s2024 as $s) {
    $studentClassMap[$s->id] = $c8List[$idx8 % 4];
    $idx8++;
}

// 5. Sumber: Siswa 2025/2026 Grade 7 (NIS 011/012/312 atau created in 2025)
$s2025Grade7 = DB::table('students')
    ->where('created_at', '<', '2026-07-01')
    ->whereNotIn('id', $locked2026StudentIds)
    ->whereNotIn('id', array_keys($studentClassMap))
    ->get();

$c7List = ['Kelas 7A', 'Kelas 7B', 'Kelas 7C', 'Kelas 7D', 'Kelas 7E'];
$idx7 = 0;
foreach ($s2025Grade7 as $s) {
    $studentClassMap[$s->id] = $c7List[$idx7 % 5];
    $idx7++;
}

echo "=== MAPPING RESULTS SUMMARY ===\n";
$byClass = [];
foreach ($studentClassMap as $sid => $cname) {
    $byClass[$cname][] = $sid;
}

ksort($byClass);
foreach ($byClass as $cname => $sids) {
    echo sprintf("%-12s : %2d students\n", $cname, count($sids));
}

echo "\nTotal mapped students for 2025/2026: " . count($studentClassMap) . "\n";
echo "Are any 2026/2027 locked students included? " . (count(array_intersect(array_keys($studentClassMap), $locked2026StudentIds)) > 0 ? "YES (ERROR!)" : "NO (100% CLEAN!)") . "\n";
