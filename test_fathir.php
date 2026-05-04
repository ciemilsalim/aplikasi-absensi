<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = \App\Models\Student::where('name', 'like', '%FATHIR%')->first();
if ($student) {
    $class = \App\Models\SchoolClass::find($student->school_class_id);
    echo json_encode(['student' => $student->name, 'class' => $class ? $class->name : 'none']) . "\n";
} else {
    echo "Student FATHIR not found\n";
}

$teacher = \App\Models\Teacher::whereHas('homeroomClass', function($q) {
    $q->where('name', 'like', '%9A%');
})->first();

if ($teacher) {
    $homeroomClass = $teacher->homeroomClass;
    echo json_encode([
        'teacher' => $teacher->name,
        'homeroomClass' => $homeroomClass ? $homeroomClass->name : 'none',
        'isHomeroomOnly_logic_test' => ($homeroomClass != null)
    ]) . "\n";
} else {
    echo "Teacher 9A not found\n";
}
