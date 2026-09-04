<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Let's check if there are any other records in attendances or other tables for 46..124
$s46_95 = DB::table('attendances')->whereBetween('student_id', [46, 95])->get();
echo "Attendances for students 46..95 count: " . $s46_95->count() . "\n";
echo "Unique dates: " . $s46_95->pluck('attendance_time')->unique()->join(', ') . "\n";

$s96_124 = DB::table('attendances')->whereBetween('student_id', [96, 124])->get();
echo "Attendances for students 96..124 count: " . $s96_124->count() . "\n";
