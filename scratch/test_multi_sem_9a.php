<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

echo "=== TESTING ALL SEMESTERS FOR 2025/2026 ===\n\n";

$years2025 = AcademicYear::where('name', 'like', '%2025%')->orWhere('id', 1)->get();
$year2025Ids = $years2025->pluck('id')->toArray();
$year2026 = AcademicYear::where('name', 'like', '%2026%')->whereNotIn('id', $year2025Ids)->first();
$lockedYearId = $year2026?->id;

$semesters2025 = Semester::whereIn('academic_year_id', $year2025Ids)
    ->where(function($q) use ($lockedYearId) {
        if ($lockedYearId) {
            $q->where('academic_year_id', '!=', $lockedYearId);
        }
        $q->where('is_active', '!=', 1);
    })
    ->get();

echo "Semesters detected for 2025/2026:\n";
foreach ($semesters2025 as $sem) {
    echo "  - Sem ID: {$sem->id} | Name: {$sem->name} | AY_ID: {$sem->academic_year_id}\n";
}

// 29 students of 9A
$nis9aList = [
    '1200', '1201', '1202', '1203', '1204', '1205', '1206', '1207', '1208', '1209',
    '1210', '1211', '1212', '1213', '1214', '1215', '1216', '1217', '1218', '1219',
    '1220', '1221', '1222', '1223', '1224', '1225', '1226', '1227', '1228'
];

$students9A = DB::table('students')->whereIn('nis', $nis9aList)->pluck('id')->toArray();
echo "\nTotal 9A students to link: " . count($students9A) . "\n";

$cls9a = SchoolClass::withoutGlobalScopes()->where('name', 'Kelas 9A')->whereIn('academic_year_id', $year2025Ids)->first();
echo "Kelas 9A ID: {$cls9a->id}\n\n";

// Sync class_student for each semester
foreach ($semesters2025 as $sem) {
    echo "Syncing for Semester {$sem->id} ({$sem->name})...\n";
    foreach ($students9A as $sid) {
        DB::table('class_student')->updateOrInsert(
            [
                'student_id' => $sid,
                'semester_id' => $sem->id,
                'academic_year_id' => $sem->academic_year_id,
            ],
            [
                'school_class_id' => $cls9a->id,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}

// Now test querying students via relation for each semester
foreach ($semesters2025 as $sem) {
    session(['active_academic_year_id' => $sem->academic_year_id, 'active_semester_id' => $sem->id]);
    $loadedStudents = $cls9a->students()->get();
    echo "Semester {$sem->id} ({$sem->name}) -> Loaded students in 9A: " . $loadedStudents->count() . "\n";
}
