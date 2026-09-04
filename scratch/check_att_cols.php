<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

print_r(Schema::getColumnListing('attendances'));
$rows = DB::table('attendances')->whereBetween('student_id', [46, 95])->limit(10)->get();
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}
