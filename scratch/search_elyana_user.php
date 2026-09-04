<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Teacher;

echo "=== SEARCH USERS FOR ELYANA OR GURU ===\n";
$users = User::all();
foreach ($users as $u) {
    if (stripos($u->name, 'ely') !== false || stripos($u->email, 'ely') !== false || $u->role === 'teacher') {
        echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Role: {$u->role}\n";
    }
}
