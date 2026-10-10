<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parents = App\Models\ParentModel::with('user', 'students')->get();
echo "Total Parents: " . $parents->count() . "\n";
foreach ($parents as $p) {
    $user = $p->user;
    echo "Parent ID=" . $p->id . " | User=" . ($user ? $user->email : 'NONE') . " | Students=" . $p->students->count() . "\n";
    foreach ($p->students as $s) {
        echo "   - Student ID=" . $s->id . " Name=" . $s->name . "\n";
    }
}
