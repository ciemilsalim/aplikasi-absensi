<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$distinctRoles = \Illuminate\Support\Facades\DB::table('users')->select('role')->distinct()->pluck('role');
$hasRolesTable = \Illuminate\Support\Facades\Schema::hasTable('roles');
$spatieRoles = $hasRolesTable ? \Illuminate\Support\Facades\DB::table('roles')->pluck('name') : 'table roles does not exist';

echo json_encode([
    'roles_in_users' => $distinctRoles,
    'has_roles_table' => $hasRolesTable,
    'spatie_roles' => $spatieRoles,
], JSON_PRETTY_PRINT);
