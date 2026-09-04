<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ALL STUDENT COHORTS FOR 2025/2026 ===\n\n";

// 1. Check all students created on 2025-07-10 (NIS 20240001..20240050)
$s2024 = DB::table('students')->where('nis', 'like', '2024%')->orderBy('nis')->get();
echo "NIS 2024xxxx count: " . $s2024->count() . "\n";
$att2024 = DB::table('attendances')->whereIn('student_id', $s2024->pluck('id'))->get();
echo "Attendance count for 2024xxxx: " . $att2024->count() . "\n";
$firstAtt2024 = $att2024->first();
if ($firstAtt2024) {
    echo "Sample att date: {$firstAtt2024->attendance_time}, status: {$firstAtt2024->status}\n";
}

// 2. Check all students created on 2025-07-22 (NIS 312..., 011..., 012...)
$sReal2025 = DB::table('students')->where('created_at', 'like', '2025-07-22%')->orderBy('id')->get();
echo "\n2025-07-22 student count: " . $sReal2025->count() . "\n";
foreach ($sReal2025->take(5) as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | ClassID: {$s->school_class_id}\n";
}

// 3. Check 2026/2027 new students (created_at >= 2026-07-01)
$s2026 = DB::table('students')->where('created_at', '>=', '2026-07-01')->orderBy('id')->get();
echo "\n2026/2027 newly enrolled students (created_at >= 2026-07-01) count: " . $s2026->count() . "\n";
foreach ($s2026 as $s) {
    echo "ID: {$s->id} | NIS: {$s->nis} | Name: {$s->name} | ClassID: {$s->school_class_id} | Created: {$s->created_at}\n";
}
