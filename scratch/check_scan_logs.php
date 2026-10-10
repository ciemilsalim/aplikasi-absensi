<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = App\Models\ScanLog::count();
echo "Total Scan Logs: " . $count . "\n";
if ($count === 0) {
    // Seed sample scan logs for today
    $today = Carbon\Carbon::today();
    $student = App\Models\Student::first();
    $admin = App\Models\User::where('role', 'admin')->first();
    
    App\Models\ScanLog::create([
        'user_id' => $admin ? $admin->id : 1,
        'user_name' => $admin ? $admin->name : 'Admin',
        'user_role' => 'admin',
        'student_id' => $student ? $student->id : 1,
        'student_nis' => $student ? $student->nis : '12345',
        'student_name' => $student ? $student->name : 'Ahmad Santoso',
        'scan_type' => 'masuk',
        'status' => 'berhasil',
        'scanned_at' => $today->copy()->setTime(7, 15, 0),
        'method' => 'rfid'
    ]);
    App\Models\ScanLog::create([
        'user_id' => $admin ? $admin->id : 1,
        'user_name' => $admin ? $admin->name : 'Admin',
        'user_role' => 'admin',
        'student_id' => $student ? $student->id : 1,
        'student_nis' => $student ? $student->nis : '12345',
        'student_name' => $student ? $student->name : 'Ahmad Santoso',
        'scan_type' => 'pulang',
        'status' => 'berhasil',
        'scanned_at' => $today->copy()->setTime(14, 0, 0),
        'method' => 'rfid'
    ]);
    echo "Created sample scan logs for today. New count: " . App\Models\ScanLog::count() . "\n";
}
