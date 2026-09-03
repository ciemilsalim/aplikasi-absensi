<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;

$schedules = Schedule::with(['schoolClass', 'teachingAssignment.schoolClass'])->get();
echo "Total Schedules: " . $schedules->count() . "\n";
foreach ($schedules->take(10) as $sch) {
    $class = $sch->getTargetClass();
    echo "Schedule ID: {$sch->id} | Day: {$sch->day_of_week} | Class: " . ($class ? $class->name . " (ID: {$class->id})" : "None") . "\n";
}
