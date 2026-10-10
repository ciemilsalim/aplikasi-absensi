<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s7 = App\Models\Schedule::find(7);
$s7->semester_id = 3;
$s7->academic_year_id = 2;
$s7->save();
echo "Schedule 7 updated to semester 3, academic year 2.\n";

$ta7 = $s7->teachingAssignment;
if ($ta7) {
    $ta7->semester_id = 3;
    $ta7->academic_year_id = 2;
    $ta7->save();
    echo "Teaching Assignment updated as well.\n";
}
