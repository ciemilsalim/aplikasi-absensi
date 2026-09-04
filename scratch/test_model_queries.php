<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

echo "=== TESTING ELYANA (WALI KELAS 9A) LOOKUP ===\n";

$teacher = Teacher::where('name', 'like', '%Elyana%')->first();
echo "Teacher: {$teacher->name} (ID: {$teacher->id})\n";

$cls9a = SchoolClass::withoutGlobalScopes()->where('name', 'Kelas 9A')->where('academic_year_id', 1)->first();
echo "Kelas 9A (AY 1): ID {$cls9a->id}, Name: {$cls9a->name}, TeacherID: {$cls9a->teacher_id}\n";

// Simulate Session for Semester 2 (2025/2026 Ganjil)
session(['active_academic_year_id' => 1, 'active_semester_id' => 2]);
$cSem2 = SchoolClass::find($cls9a->id);
echo "Sem 2 (Ganjil 2025) Students Count: " . ($cSem2 ? $cSem2->students()->count() : 0) . "\n";

// Simulate Session for Semester 1 (2025/2026 Genap)
session(['active_academic_year_id' => 1, 'active_semester_id' => 1]);
$cSem1 = SchoolClass::find($cls9a->id);
echo "Sem 1 (Genap 2025) Students Count: " . ($cSem1 ? $cSem1->students()->count() : 0) . "\n";

// Simulate Session for Semester 3 (2026/2027 Ganjil)
session(['active_academic_year_id' => 2, 'active_semester_id' => 3]);
$cSem3 = SchoolClass::where('academic_year_id', 2)->get();
echo "Sem 3 (2026/2027) Classes Count: " . $cSem3->count() . "\n";
foreach ($cSem3 as $c) {
    echo " - Class ID {$c->id}: {$c->name}, Students: " . $c->students()->count() . "\n";
}
