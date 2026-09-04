<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

print_r(Schema::getColumnListing('school_classes'));
$classes = DB::table('school_classes')->get();
foreach ($classes as $c) {
    echo json_encode($c) . "\n";
}
