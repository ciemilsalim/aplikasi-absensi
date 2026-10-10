<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'orang_tua')->first();
if ($user) {
    echo "PARENT USER: " . $user->email . " (id=" . $user->id . ", name=" . $user->name . ")\n";
    $students = App\Models\Siswa::where('orang_tua_id', $user->id)->get();
    echo "Siswa count: " . $students->count() . "\n";
    foreach ($students as $s) {
        echo "  Child: " . $s->nama . " (id=" . $s->id . ")\n";
    }
} else {
    echo "No orang_tua user found.\n";
}
