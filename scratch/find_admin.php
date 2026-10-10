<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admins = App\Models\User::where('role', 'admin')->orWhereHas('roles', function($q){ $q->where('name', 'admin'); })->take(5)->get();
foreach ($admins as $a) {
    echo "ADMIN: " . $a->name . " | " . $a->email . "\n";
}

$teachers = App\Models\User::where('role', 'teacher')->orWhereHas('roles', function($q){ $q->where('name', 'teacher'); })->take(3)->get();
foreach ($teachers as $t) {
    echo "TEACHER: " . $t->name . " | " . $t->email . "\n";
}
