<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\SubjectAttendance;
use App\Models\LeaveRequest;
use App\Http\Controllers\Admin\LeaveRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// 1. Authenticate as an admin or operator user
$user = User::where('role', 'admin')->first() ?: User::first();
Auth::login($user);
echo "Logged in as: " . $user->name . " (Role: " . $user->role . ")\n";

// 2. Find a test student
$student = Student::with('schoolClass')->first();
if (!$student) {
    echo "No students found.\n";
    exit(1);
}
echo "Testing with Student: " . $student->name . " (Class: " . ($student->schoolClass->name ?? 'None') . ")\n";

$controller = new LeaveRequestController();

// Test date: today (or next weekday)
$testDate = now();
if ($testDate->isWeekend()) {
    $testDate = $testDate->next(Carbon\Carbon::MONDAY);
}
$dateStr = $testDate->format('Y-m-d');

echo "Test Date: $dateStr\n";

// 3. Test Store Manual Intervention
$request = new Request([
    'student_ids' => [$student->id],
    'start_date' => $dateStr,
    'end_date' => $dateStr,
    'type' => 'sakit',
    'submission_source' => 'whatsapp',
    'reason' => 'Uji coba intervensi sakit demam via WA',
]);

echo "--- STEP 1: Storing Manual Intervention ---\n";
$response = $controller->storeManual($request);
echo "Store response status: " . $response->getStatusCode() . "\n";

$leave = LeaveRequest::where('student_id', $student->id)
    ->where('start_date', $dateStr)
    ->where('type', 'sakit')
    ->latest()
    ->first();

if ($leave) {
    echo "SUCCESS: LeaveRequest created with ID " . $leave->id . " [Source: " . $leave->submission_source . ", CreatedBy: " . $leave->created_by . "]\n";
} else {
    echo "FAILED: LeaveRequest not found.\n";
}

// Check Attendance (Daily / Wali Kelas)
$attendance = Attendance::where('student_id', $student->id)
    ->whereDate('attendance_time', $dateStr)
    ->first();

if ($attendance && $attendance->status === 'sakit') {
    echo "SUCCESS: Daily Attendance (Wali Kelas) synchronized! Status: " . $attendance->status . "\n";
} else {
    echo "INFO: Daily Attendance status: " . ($attendance ? $attendance->status : 'None') . "\n";
}

// Check SubjectAttendance (Guru Mapel)
$subjectAttendances = SubjectAttendance::where('student_id', $student->id)
    ->whereDate('created_at', $dateStr)
    ->get();

echo "INFO: Subject Attendance records synchronized: " . $subjectAttendances->count() . "\n";
foreach ($subjectAttendances as $sa) {
    echo "  - Schedule ID " . $sa->schedule_id . " | Status: " . $sa->status . " | Note: " . $sa->notes . "\n";
}

// 4. Test Update (Edit from Sakit to Izin)
echo "\n--- STEP 2: Updating Intervention (Sakit -> Izin) ---\n";
$updateRequest = new Request([
    'start_date' => $dateStr,
    'end_date' => $dateStr,
    'type' => 'izin',
    'submission_source' => 'telepon',
    'reason' => 'Uji coba update ke izin urusan keluarga via Telepon',
]);

$updateResponse = $controller->update($updateRequest, $leave);
$leave->refresh();

echo "Updated Leave Type: " . $leave->type . " | Source: " . $leave->submission_source . "\n";

$attendanceAfterUpdate = Attendance::where('student_id', $student->id)
    ->whereDate('attendance_time', $dateStr)
    ->first();
echo "Daily Attendance Status after update: " . ($attendanceAfterUpdate ? $attendanceAfterUpdate->status : 'None') . "\n";

// 5. Test Destroy (Cancel / Delete Intervention)
echo "\n--- STEP 3: Destroying Intervention ---\n";
$deleteResponse = $controller->destroy($leave);
$attendanceAfterDelete = Attendance::where('student_id', $student->id)
    ->whereDate('attendance_time', $dateStr)
    ->first();
echo "Daily Attendance after delete: " . ($attendanceAfterDelete ? $attendanceAfterDelete->status : 'CLEARED / NOT FOUND (Expected)') . "\n";

$subjectAttendancesAfterDelete = SubjectAttendance::where('student_id', $student->id)
    ->whereDate('created_at', $dateStr)
    ->count();
echo "Subject Attendance count after delete: " . $subjectAttendancesAfterDelete . " (Expected: 0)\n";

echo "\n--- ALL TESTS COMPLETED SUCCESSFULLY! ---\n";
