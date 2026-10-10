<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'emil@gmail.com')->first();
echo "User ID: " . $user->id . "\n";
echo "Parent relation exists: " . ($user->parent ? 'YES, parent_id=' . $user->parent->id : 'NO') . "\n";
if ($user->parent) {
    echo "Students exists: " . ($user->parent->students()->exists() ? 'YES' : 'NO') . "\n";
    echo "Students count: " . $user->parent->students()->count() . "\n";
} else {
    // Check parents table
    $p = DB::table('parents')->where('user_id', $user->id)->first();
    echo "Row in parents table with user_id=" . $user->id . ": " . ($p ? json_encode($p) : 'NONE') . "\n";
}
