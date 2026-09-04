<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

echo "=== ALL TEACHERS ===\n";
$teachers = Teacher::all();
foreach ($teachers as $t) {
    echo "Teacher ID: {$t->id} | Name: {$t->name} | User ID: {$t->user_id}\n";
}

echo "\n=== ALL USERS WITH ROLE TEACHER ===\n";
$users = User::where('role', 'teacher')->get();
foreach ($users as $u) {
    echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email}\n";
}
