<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'cici@gmail.com')->first();
$parent = App\Models\ParentModel::where('user_id', $user->id)->first();
if ($parent) {
    $parent->students()->detach();
    App\Models\ParentStudentRequest::where('parent_id', $parent->id)->delete();
    $parent->phone_number = '';
    $parent->address = '';
    $parent->is_onboarding_completed = false;
    $parent->save();
    echo "Parent cici reset successfully. Students detached, phone cleared.\n";
}
