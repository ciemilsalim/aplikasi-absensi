<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'ahmad.subarjo@example.com')->first();
if ($user) {
    App\Models\ParentModel::where('user_id', $user->id)->delete();
    $user->delete();
    echo "Deleted user ahmad.subarjo@example.com and related records.\n";
} else {
    echo "User not found, ready.\n";
}
