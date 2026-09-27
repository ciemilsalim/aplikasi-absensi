<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

$email = 'siasek_evidence@example.com';
$name = 'SIASEK Evidence';

// Read password from environment or .env.siasek-bos
$password = env('SIASEK_EVIDENCE_PASSWORD');
if (empty($password)) {
    $envFile = __DIR__ . '/../.env.siasek-bos';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'SIASEK_EVIDENCE_PASSWORD=') === 0) {
                $password = trim(substr($line, strlen('SIASEK_EVIDENCE_PASSWORD=')));
            }
        }
    }
}

if (empty($password)) {
    echo "ERROR: Password SIASEK_EVIDENCE_PASSWORD tidak ditemukan!\n";
    exit(1);
}

// Clean up duplicate old accounts if any
$users = User::where('email', $email)
    ->orWhere('email', 'siasek_evidence@smpn1biau.sch.id')
    ->orWhere('name', 'siasek_evidence')
    ->get();

if ($users->count() > 1) {
    echo "Found " . $users->count() . " matching users. Merging to single user...\n";
    $firstUser = $users->first();
    foreach ($users as $index => $u) {
        if ($index > 0) {
            DB::table('model_has_roles')->where('model_id', $u->id)->delete();
            $u->delete();
            echo "Deleted duplicate user ID: {$u->id}\n";
        }
    }
    $user = $firstUser;
} else {
    $user = $users->first();
}

if (!$user) {
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => 'viewer',
        'email_verified_at' => now(),
    ]);
    echo "Created user {$email}.\n";
} else {
    $user->name = $name;
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->role = 'viewer';
    $user->save();
    echo "Updated user ID {$user->id} to email {$email}.\n";
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

$totalEvidenceUsers = User::where('role', 'viewer')->count();
echo "Local user verification complete. ID: {$user->id}, Email: {$user->email}, Role: {$user->role}. Total viewer accounts: {$totalEvidenceUsers}\n";
