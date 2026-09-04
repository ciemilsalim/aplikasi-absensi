<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

echo "=== Distinct Statuses in students table ===\n";
$statuses = DB::table('students')->select('status', DB::raw('count(*) as total'))->groupBy('status')->get();
foreach ($statuses as $st) {
    echo "Status: [" . ($st->status ?? 'NULL') . "] => Total: " . $st->total . "\n";
}

echo "\n=== Inactive Students Sample ===\n";
$inactives = DB::table('students')->where('status', '!=', 'aktif')->whereNotNull('status')->limit(10)->get();
foreach ($inactives as $in) {
    echo "ID: {$in->id} | Name: {$in->name} | Status: {$in->status} | ClassID: {$in->school_class_id}\n";
}
