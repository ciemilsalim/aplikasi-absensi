<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::take(10)->get(['id', 'name', 'email', 'role']);
foreach ($users as $u) {
    echo "USER: {$u->name} | {$u->email} | Role: {$u->role}\n";
}
