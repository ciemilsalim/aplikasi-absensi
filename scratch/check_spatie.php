<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = \Illuminate\Support\Facades\DB::table('roles')->get();
$permissionsCount = \Illuminate\Support\Facades\Schema::hasTable('permissions') ? \Illuminate\Support\Facades\DB::table('permissions')->count() : 0;
$permissions = \Illuminate\Support\Facades\Schema::hasTable('permissions') ? \Illuminate\Support\Facades\DB::table('permissions')->get() : [];

echo json_encode([
    'roles' => $roles,
    'permissions_count' => $permissionsCount,
    'permissions' => $permissions,
], JSON_PRETTY_PRINT);
