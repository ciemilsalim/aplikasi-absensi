<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'siasek_evidence@smpn1biau.sch.id')
    ->orWhere('name', 'siasek_evidence')
    ->first();

echo json_encode([
    'exists' => $user !== null,
    'user' => $user ? $user->only(['id', 'name', 'email', 'role']) : null
], JSON_PRETTY_PRINT);
