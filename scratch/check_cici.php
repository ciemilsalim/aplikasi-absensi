<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'cici@gmail.com')->first();
$parent = App\Models\ParentModel::where('user_id', $user->id)->with('students')->first();
echo "User: " . $user->name . " (" . $user->email . ")\n";
echo "Parent ID: " . $parent->id . "\n";
echo "Phone: " . $parent->phone_number . "\n";
echo "Onboarding completed: " . ($parent->onboarding_completed ? 'YES' : 'NO') . "\n";
echo "Connected students count: " . $parent->students->count() . "\n";
foreach ($parent->students as $s) {
    echo " - Student: " . $s->name . " (NIS: " . $s->nis . ", ID: " . $s->id . ")\n";
}
$pending = App\Models\ParentStudentVerification::where('parent_id', $parent->id)->get();
echo "Pending verifications count: " . $pending->count() . "\n";
foreach ($pending as $v) {
    echo " - Verification: Student ID " . $v->student_id . " (Status: " . $v->status . ")\n";
}
