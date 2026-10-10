<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s7 = App\Models\Schedule::find(7);
echo "S7 school_class_id: {$s7->school_class_id}\n";
$ta7 = $s7->teachingAssignment;
echo "TA7 school_class_id: {$ta7?->school_class_id}\n";

$c = App\Models\SchoolClass::where('name', 'like', '%7A%')->first();
if ($c) {
    $s7->school_class_id = $c->id;
    $s7->save();
    if ($ta7) {
        $ta7->school_class_id = $c->id;
        $ta7->save();
    }
    echo "Assigned Class 7A (ID: {$c->id}) to Schedule 7.\n";
}
