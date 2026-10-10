<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Attendance;
use App\Models\AttendanceAuditLog;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Setting;
use App\Services\Admin\AdminLeaveRequestService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'school_name'], ['value' => 'SMP Negeri 1 Biau']);
    }

    public function test_admin_can_view_audit_logs_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'name' => 'Fajar Pratama',
            'nis' => '8001',
            'unique_id' => (string) Str::uuid(),
        ]);

        AttendanceAuditLog::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_nis' => $student->nis,
            'attendance_date' => Carbon::today()->toDateString(),
            'old_status' => 'alpa',
            'new_status' => 'sakit',
            'reason' => 'Surat keterangan dokter',
            'action_type' => 'manual_correction',
            'changed_by_user_id' => $admin->id,
            'changed_by_name' => $admin->name,
            'changed_by_role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit_logs.index'));

        $response->assertOk()
            ->assertSee('Audit Trail & Log Koreksi Presensi')
            ->assertSee('Fajar Pratama')
            ->assertSee('Surat keterangan dokter');
    }

    public function test_kepala_sekolah_and_viewer_can_view_audit_logs(): void
    {
        $principal = User::factory()->create(['role' => 'kepala_sekolah']);
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($principal)->get(route('admin.audit_logs.index'))->assertOk();
        $this->actingAs($viewer)->get(route('admin.audit_logs.index'))->assertOk();
    }

    public function test_admin_can_manually_correct_attendance_and_generates_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Operator TU']);
        $student = Student::create([
            'name' => 'Najwa Dayani',
            'nis' => '8002',
            'unique_id' => (string) Str::uuid(),
        ]);

        $today = Carbon::today();
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => $today->copy()->setTime(8, 0, 0),
            'status' => 'alpa',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.audit_logs.correct'), [
            'student_id' => $student->id,
            'attendance_date' => $today->toDateString(),
            'new_status' => 'sakit',
            'reason' => 'Orang tua menyerahkan surat dokter dari Puskesmas Biau',
            'notes' => 'Surat fisik disimpan di TU',
        ]);

        $response->assertRedirect();

        // 1. Verify attendance status updated
        $attendance->refresh();
        $this->assertEquals('sakit', $attendance->status);

        // 2. Verify immutable audit log record created
        $this->assertDatabaseHas('attendance_audit_logs', [
            'student_id' => $student->id,
            'old_status' => 'alpa',
            'new_status' => 'sakit',
            'reason' => 'Orang tua menyerahkan surat dokter dari Puskesmas Biau',
            'changed_by_name' => 'Operator TU',
            'action_type' => 'manual_correction',
        ]);
    }

    public function test_correction_fails_when_reason_is_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'name' => 'Siswa Test',
            'nis' => '8003',
            'unique_id' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.audit_logs.correct'), [
            'student_id' => $student->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'new_status' => 'sakit',
            // Reason missing!
        ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_teacher_override_automatically_generates_audit_log(): void
    {
        Carbon::setTestNow('2026-10-12 09:00:00'); // Senin kerja

        $teacherUser = User::factory()->create(['role' => 'teacher', 'name' => 'Bapak Guru']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'name' => 'Bapak Guru',
            'nip' => '198501012010011001',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => '9-B',
            'teacher_id' => $teacher->id,
        ]);

        $student = Student::create([
            'name' => 'Rizky Siswa',
            'nis' => '8004',
            'school_class_id' => $schoolClass->id,
            'unique_id' => (string) Str::uuid(),
        ]);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => Carbon::today()->setTime(7, 30, 0),
            'status' => 'alpa',
        ]);

        $response = $this->actingAs($teacherUser)->patchJson("/api/teacher/students/{$student->id}/attendance/override", [
            'status' => 'izin',
            'date' => Carbon::today()->toDateString(),
            'notes' => 'Izin menjenguk keluarga sakit',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $attendance->refresh();
        $this->assertEquals('izin', $attendance->status);

        // Verify audit log generated for teacher override
        $this->assertDatabaseHas('attendance_audit_logs', [
            'student_id' => $student->id,
            'old_status' => 'alpa',
            'new_status' => 'izin',
            'reason' => 'Izin menjenguk keluarga sakit',
            'action_type' => 'teacher_override',
        ]);
    }

    public function test_leave_request_sync_automatically_generates_audit_log(): void
    {
        Carbon::setTestNow('2026-10-12 09:00:00'); // Senin kerja

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Staf Tata Usaha']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $sem = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $schoolClass = SchoolClass::create([
            'name' => '8-A',
            'academic_year_id' => $year->id,
            'semester_id' => $sem->id,
        ]);

        $student = Student::create([
            'name' => 'Aulia Putri',
            'nis' => '8005',
            'school_class_id' => $schoolClass->id,
            'unique_id' => (string) Str::uuid(),
        ]);

        // Prior attendance is alpa
        Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => Carbon::today()->startOfDay(),
            'status' => 'alpa',
        ]);

        $leaveService = app(AdminLeaveRequestService::class);
        $leaveService->storeManualIntervention(
            [
                'student_ids' => [$student->id],
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'type' => 'izin',
                'submission_source' => 'surat',
                'reason' => 'Dispensasi Lomba Sains',
            ],
            null,
            $admin->id
        );

        // Verify audit log generated for leave sync
        $this->assertDatabaseHas('attendance_audit_logs', [
            'student_id' => $student->id,
            'old_status' => 'alpa',
            'new_status' => 'izin',
            'action_type' => 'leave_sync',
        ]);
    }
}
