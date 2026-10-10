<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s19 = App\Models\Schedule::with(['teachingAssignment.teacher.user', 'teachingAssignment.schoolClass', 'teachingAssignment.subject'])->find(19);
echo "Schedule 19 Teacher: " . ($s19->teachingAssignment?->teacher?->user?->name) . " (" . ($s19->teachingAssignment?->teacher?->user?->email) . ")\n";

$ta7 = App\Models\Schedule::find(7)->teachingAssignment;
echo "Schedule 7 TA: Teacher ID: {$ta7?->teacher_id}, Subject: " . ($ta7?->subject?->name) . ", Class: " . ($ta7?->schoolClass?->name) . "\n";
