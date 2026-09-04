<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Semester;
use App\Models\Attendance;
use App\Models\SubjectAttendance;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo "   COMPREHENSIVE MULTI-SEMESTER VERIFICATION TEST       \n";
echo "========================================================\n\n";

$semesters = Semester::with('academicYear')->get();

foreach ($semesters as $sem) {
    echo "========================================================\n";
    echo "TESTING SEMESTER {$sem->id}: {$sem->name} (Year: {$sem->academicYear->name} - ID: {$sem->academic_year_id}, is_active: {$sem->is_active})\n";
    echo "========================================================\n";

    // Set session
    session([
        'active_semester_id' => $sem->id,
        'active_academic_year_id' => $sem->academic_year_id,
    ]);

    // 1. Check School Classes loaded for this semester
    $classes = SchoolClass::withCount('students')->get();
    echo "Found " . $classes->count() . " classes:\n";
    foreach ($classes as $c) {
        $studentList = $c->students()->get();
        echo "  - Class ID {$c->id}: {$c->name} | Students Count (via withCount): {$c->students_count} | Query Count: " . $studentList->count() . "\n";
    }

    // 2. Check Daily Attendances for this semester
    $dailyAttCount = Attendance::count();
    echo "Total Daily Attendances in Sem {$sem->id}: {$dailyAttCount}\n";

    // 3. Check Subject Attendances for this semester
    $subjectAttCount = SubjectAttendance::count();
    echo "Total Subject Attendances in Sem {$sem->id}: {$subjectAttCount}\n";

    // 4. Test Teaching Assignments & Schedules enrolled students
    $schedules = Schedule::where('semester_id', $sem->id)->get();
    echo "Total Schedules in Sem {$sem->id}: " . $schedules->count() . "\n";
    foreach ($schedules->take(3) as $sch) {
        $enrolled = $sch->getEnrolledStudents();
        echo "  - Schedule ID {$sch->id} ({$sch->getActivityName()}): Enrolled students: " . $enrolled->count() . "\n";
    }
    echo "\n";
}
