<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Semester;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

echo "=== Academic Years ===\n";
foreach (AcademicYear::all() as $ay) {
    echo "AY ID: {$ay->id} | Name: {$ay->name}\n";
}

echo "\n=== Semesters ===\n";
foreach (Semester::all() as $s) {
    echo "Sem ID: {$s->id} | AY ID: {$s->academic_year_id} | Name: {$s->name} | is_active: {$s->is_active}\n";
}

echo "\n=== School Classes ===\n";
foreach (SchoolClass::all() as $c) {
    echo "Class ID: {$c->id} | AY ID: {$c->academic_year_id} | Sem ID: {$c->semester_id} | Name: {$c->name}\n";
}

echo "\n=== Students Count by Status ===\n";
$stCounts = DB::table('students')->select('status', DB::raw('count(*) as cnt'))->groupBy('status')->get();
foreach ($stCounts as $sc) {
    echo "Status: [{$sc->status}] => Count: {$sc->cnt}\n";
}

echo "\n=== Class Student Pivot Count by Semester ===\n";
$csCounts = DB::table('class_student')->select('semester_id', DB::raw('count(*) as cnt'))->groupBy('semester_id')->get();
foreach ($csCounts as $cs) {
    echo "Sem ID: {$cs->semester_id} => Pivot count: {$cs->cnt}\n";
}
