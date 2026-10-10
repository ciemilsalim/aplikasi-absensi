<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'wali.santoso@example.com')->first();
if ($user) {
    echo "Found user: " . $user->name . " / " . $user->email . "\n";
    // Delete so registration test succeeds cleanly
    $user->delete();
    echo "Deleted existing test user.\n";
} else {
    echo "User not found, ready for registration.\n";
}
