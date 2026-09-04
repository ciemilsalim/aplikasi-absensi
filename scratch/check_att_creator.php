<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$att46_95 = DB::table('attendances as a')
    ->leftJoin('users as u', 'a.created_by', '=', 'u.id')
    ->whereBetween('a.student_id', [46, 95])
    ->select('a.*', 'u.name as creator_name')
    ->get();

echo "Count: " . $att46_95->count() . "\n";
foreach ($att46_95->take(15) as $r) {
    echo json_encode($r) . "\n";
}
