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

echo "========================================================\n";
echo "   DYNAMIC DATABASE SYNC & RECONSTRUCTION ENGINE        \n";
echo "========================================================\n\n";

// 1. Identify Academic Year 2025/2026
$year2025 = AcademicYear::where('name', 'like', '%2025%')->first();
if (!$year2025) {
    $year2025 = AcademicYear::orderBy('id')->first();
}
echo "Target Year 2025/2026: ID {$year2025->id} ({$year2025->name})\n";

// Get semesters for 2025/2026
$semesters2025 = Semester::where('academic_year_id', $year2025->id)->get();
echo "Semesters in {$year2025->name}: " . $semesters2025->pluck('name', 'id') . "\n\n";

// 2. Scan all school classes
$classes = DB::table('school_classes')->get();
echo "Existing Classes in DB: " . $classes->count() . "\n";
foreach ($classes as $c) {
    echo "  Class ID {$c->id}: {$c->name} | year: {$c->academic_year_id} | teacher_id: {$c->teacher_id}\n";
}

// 3. Dynamic student mapping logic
$classMapping = [
    'Kelas 9A' => ['1200', '1201', '1202', '1203', '1204', '1205', '1206', '1207'],
    'Kelas 8A' => ['1208', '1209', '1210', '1211', '1212', '1213', '1214', '1215'],
    'Kelas 7B' => ['1216', '1217', '1218', '1219', '1220', '1221', '1222', '1223', '1224', '1225', '1226', '1227', '1228'],
    'Kelas 7A' => ['1001', '1002', '1003', '1004', '1005', '1010', '1301', '1400', '1401', '20240027', '20240028', '20240029', '20240030', '20240031', '20240032', '20240033', '20240034', '20240035', '20240036', '20240037', '20240038', '20240039', '20240040', '20240041', '20240042', '20240043', '20240044', '20240045', '20240046', '20240047', '20240048', '20240049', '20240050'],
    'Kelas 7C' => ['20240001', '20240002', '20240003', '20240004', '20240005', '20240006', '20240007', '20240008', '20240009', '20240010', '20240011', '20240012', '20240013', '20240014', '20240015', '20240016', '20240017', '20240018', '20240019', '20240020', '20240021', '20240022', '20240023', '20240024', '20240025', '20240026', '3121539431', '0127573197', '0123309910', '0124534914', '0128316927', '0114768421', '0117970706'],
    'X-1' => ['20250101', '20250102', '20250103', '20250104', '20250105', '20250106', '20250107', '20250108', '20250109', '20250110'],
    'X-2' => ['20250201', '20250202', '20250203', '20250204', '20250205', '20250206', '20250207', '20250208', '20250209', '20250210'],
    'XI-1' => ['20250301', '20250302', '20250303', '20250304', '20250305', '20250306', '20250307', '20250308', '20250309', '20250310'],
];

echo "\n--- DYNAMIC RESOLUTION FOR EACH CLASS IN 2025/2026 ---\n";
foreach ($classMapping as $className => $nisList) {
    // Find class for year 2025/2026
    $class = SchoolClass::withoutGlobalScopes()
        ->where('name', $className)
        ->where(function($q) use ($year2025) {
            $q->where('academic_year_id', $year2025->id)
              ->orWhereNull('academic_year_id')
              ->orWhere('created_at', '<', '2026-07-01');
        })
        ->first();

    if (!$class) {
        echo "Class '{$className}' not found for Year 2025/2026! Creating it...\n";
        $class = SchoolClass::create([
            'name' => $className,
            'level_id' => str_contains($className, '7') || str_contains($className, 'X-') ? 1 : (str_contains($className, '8') || str_contains($className, 'XI-') ? 2 : 3),
            'academic_year_id' => $year2025->id,
            'semester_id' => $semesters2025->first()?->id,
        ]);
    } else {
        // Ensure academic_year_id is set
        if ($class->academic_year_id != $year2025->id) {
            DB::table('school_classes')->where('id', $class->id)->update(['academic_year_id' => $year2025->id]);
        }
    }

    // Match students by NIS, history, and footprints
    $students = Student::whereIn('nis', $nisList)
        ->orWhere('school_class_id', $class->id)
        ->get();

    echo "Class ID {$class->id} ({$class->name}): Found " . $students->count() . " students.\n";

    // Insert into class_student for each semester
    foreach ($semesters2025 as $sem) {
        foreach ($students as $st) {
            DB::table('class_student')->updateOrInsert(
                [
                    'student_id' => $st->id,
                    'semester_id' => $sem->id,
                ],
                [
                    'school_class_id' => $class->id,
                    'academic_year_id' => $year2025->id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

echo "\nSYNC COMPLETE! Checking class_student totals:\n";
$summary = DB::table('class_student as cs')
    ->join('school_classes as sc', 'cs.school_class_id', '=', 'sc.id')
    ->select('cs.semester_id', 'sc.name', DB::raw('count(*) as total'))
    ->groupBy('cs.semester_id', 'sc.name')
    ->orderBy('cs.semester_id')
    ->get();
foreach ($summary as $row) {
    echo "Semester {$row->semester_id} | {$row->name}: {$row->total} students\n";
}
