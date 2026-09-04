<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\TeachingAssignment;
use App\Models\Schedule;
use App\Models\Semester;

echo "=== SIMULATING DASHBOARD FOR ALL TEACHERS IN SEMESTER 1 (2025/2026 Genap) ===\n";
session(['active_semester_id' => 1, 'active_academic_year_id' => 1]);

$teachers = Teacher::all();
foreach ($teachers as $t) {
    $isHomeroomTeacher = $t->homeroomClass()->exists();
    $isSubjectTeacher = $t->teachingAssignments()->exists();
    $isExtracurricularCoach = $t->coachingExtracurriculars()->exists();
    $isCocurricularFacilitator = $t->cocurriculars()->exists() 
        || Schedule::where('teacher_id', $t->id)->where('schedule_type', 'cocurricular')->exists();

    $homeroomClass = $t->homeroomClass;
    $taCount = $t->teachingAssignments()->count();

    echo "Teacher ID {$t->id} ({$t->name}):\n";
    echo "  - isHomeroom: " . ($isHomeroomTeacher ? "YES (Class: {$homeroomClass?->name})" : "NO") . "\n";
    echo "  - isSubject: " . ($isSubjectTeacher ? "YES ({$taCount} assignments)" : "NO") . "\n";
    echo "  - isEkskul: " . ($isExtracurricularCoach ? "YES" : "NO") . "\n";
    echo "  - isKokurikuler: " . ($isCocurricularFacilitator ? "YES" : "NO") . "\n";
    if (!$isHomeroomTeacher && !$isSubjectTeacher && !$isExtracurricularCoach && !$isCocurricularFacilitator) {
        echo "  ==> RESULT: 'Anda Belum Memiliki Peran' (NO ROLE!)\n";
    } else {
        echo "  ==> RESULT: ACTIVE ROLE FOUND!\n";
    }
}
