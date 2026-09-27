<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

$username = 'siasek_evidence';
$email = 'siasek_evidence@smpn1biau.sch.id';

// Read password from .env.siasek-bos or .env
$envFile = __DIR__ . '/../.env.siasek-bos';
$password = 'qwerty123';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'SIASEK_EVIDENCE_PASSWORD=') === 0) {
            $password = trim(substr($line, strlen('SIASEK_EVIDENCE_PASSWORD=')));
        }
    }
}

$user = User::where('name', $username)->orWhere('email', $email)->first();
if (!$user) {
    $user = User::create([
        'name' => $username,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => 'viewer',
        'email_verified_at' => now(),
    ]);
    echo "Created user {$username}.\n";
} else {
    $user->password = Hash::make($password);
    $user->role = 'viewer';
    $user->save();
    echo "Updated password for local user {$username}.\n";
}

$roleId = DB::table('roles')->where('name', 'viewer')->value('id');
if (!$roleId) {
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'viewer',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

$hasRolePivot = DB::table('model_has_roles')
    ->where('role_id', $roleId)
    ->where('model_type', get_class($user))
    ->where('model_id', $user->id)
    ->exists();

if (!$hasRolePivot) {
    DB::table('model_has_roles')->insert([
        'role_id' => $roleId,
        'model_type' => get_class($user),
        'model_id' => $user->id,
    ]);
}

echo "Local user verification complete. ID: {$user->id}, Role: {$user->role}\n";
