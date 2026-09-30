<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'parent')->first();
if ($user && $user->parent) {
    $parentModel = $user->parent;
    if ($parentModel->students()->count() == 0) {
        $student = App\Models\Student::first();
        if ($student) {
            $parentModel->students()->attach($student->id);
            echo "Attached student ID " . $student->id . " (" . $student->name . ") to parent " . $user->email . "\n";
        }
    }
    $user->password = bcrypt('password');
    $user->save();
    
    echo "PARENT DEMO CREDENTIALS:\n";
    echo "Email: " . $user->email . "\n";
    echo "Password: password\n";
    echo "Students:\n";
    foreach ($parentModel->students as $st) {
        echo " - ID: " . $st->id . " | Name: " . $st->name . "\n";
    }
}
