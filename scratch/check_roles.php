<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = App\Models\User::select('role')->distinct()->pluck('role');
echo "Roles in users table:\n";
foreach ($roles as $r) {
    echo " - " . ($r ?: '(null)') . "\n";
}

$sampleUsers = App\Models\User::take(10)->get(['id', 'name', 'email', 'role']);
echo "\nSample Users:\n";
foreach ($sampleUsers as $u) {
    echo " - " . $u->email . " | role=" . $u->role . " | name=" . $u->name . "\n";
}
