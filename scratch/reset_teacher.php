<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'budi.guru@mokopani.sch.id')->first();
if (!$user) {
    $user = App\Models\User::where('role', 'guru')->first();
}
if ($user) {
    $user->password = bcrypt('password');
    $user->save();
    echo "SUCCESS: Teacher user set - Email: " . $user->email . " (Password: password)\n";
} else {
    echo "ERROR: No teacher found.\n";
}
