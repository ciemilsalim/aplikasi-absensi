<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teacherUser = App\Models\User::where('email', 'jalil@gmail.com')->first();
$teacher = $teacherUser->teacher;
echo "Teacher: {$teacherUser->name} (ID: {$teacher->id})\n";

$schedules = App\Models\Schedule::whereHas('teachingAssignment', function($q) use ($teacher) {
    $q->where('teacher_id', $teacher->id);
})->get();

echo "Schedules count: " . $schedules->count() . "\n";
foreach ($schedules as $s) {
    echo "  Schedule ID: {$s->id} | Day: {$s->day_of_week} | Class: " . ($s->getTargetClass()?->name ?? '-') . " | Subject: " . $s->getActivityName() . "\n";
}
