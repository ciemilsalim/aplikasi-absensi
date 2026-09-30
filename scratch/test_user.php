<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'budi.guru@mokopani.sch.id')->first();
if ($user) {
    $user->password = bcrypt('password');
    $user->save();
    echo "Password set for " . $user->email . "\n";
    $teacher = $user->teacher;
    echo "Teacher ID: " . ($teacher ? $teacher->id : 'none') . "\n";
    if ($teacher) {
        $schedules = App\Models\Schedule::whereHas('teachingAssignment', function($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->get();
        echo "Schedules count: " . $schedules->count() . "\n";
        foreach ($schedules as $s) {
            echo "Schedule ID: " . $s->id . " - Class: " . ($s->getTargetClass()?->name ?? '-') . " - Subject: " . $s->getActivityName() . "\n";
        }
    }
}
