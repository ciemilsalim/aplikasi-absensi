<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SubjectAttendance;
use App\Models\LeaveRequest;
use App\Http\Controllers\Admin\LeaveRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// 1. Authenticate as an admin
$user = User::where('role', 'admin')->first() ?: User::first();
Auth::login($user);

// 2. Student in Kelas 7A (ID: 1)
$student = Student::where('school_class_id', 1)->first();
echo "Student: " . $student->name . " in Kelas 7A\n";

$controller = new LeaveRequestController();

// Pick a Monday (Day 1) date: e.g. 2026-09-07
$testDate = '2026-09-07'; // Monday
echo "Test Monday Date: $testDate\n";

$request = new Request([
    'student_ids' => [$student->id],
    'start_date' => $testDate,
    'end_date' => $testDate,
    'type' => 'sakit',
    'submission_source' => 'whatsapp',
    'reason' => 'Demam tinggi, istirahat dokter (via WA)',
]);

echo "--- Storing Manual Intervention for Monday ---\n";
$controller->storeManual($request);

$leave = LeaveRequest::where('student_id', $student->id)
    ->where('start_date', $testDate)
    ->latest()
    ->first();

echo "LeaveRequest ID: " . $leave->id . "\n";

// Daily Attendance Check
$daily = Attendance::where('student_id', $student->id)
    ->whereDate('attendance_time', $testDate)
    ->first();
echo "Daily Attendance Status: " . ($daily ? $daily->status : 'None') . "\n";

// Subject Attendance Check
$subjects = SubjectAttendance::where('student_id', $student->id)
    ->whereDate('created_at', $testDate)
    ->get();
echo "Subject Attendance count on Monday: " . $subjects->count() . "\n";
foreach ($subjects as $sa) {
    echo "  -> Schedule ID: {$sa->schedule_id} | Status: {$sa->status} | Note: {$sa->notes}\n";
}

// Clean up
$controller->destroy($leave);
echo "Cleaned up test record.\n";
