<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teacher = App\Models\User::where('email', 'jalil@gmail.com')->first()->teacher;
$schedules = App\Models\Schedule::with(['teachingAssignment.schoolClass', 'teachingAssignment.subject'])
    ->whereHas('teachingAssignment', function ($q) use ($teacher) {
        $q->where('teacher_id', $teacher->id);
    })->get();

echo 'Schedule count: ' . $schedules->count() . "\n";
foreach ($schedules as $s) {
    echo 'ID: ' . $s->id . ' - Class: ' . ($s->getTargetClass()?->name ?? '-') . ' - Subject: ' . $s->getActivityName() . "\n";
}
