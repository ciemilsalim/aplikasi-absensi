<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupAdminContext(): array
    {
        Carbon::setTestNow('2026-10-12 10:00:00');

        $admin = User::factory()->create(['role' => 'admin']);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'name' => 'Wali Kelas VII-A',
            'nip' => '198001012005011001',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $student = Student::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Citra Lestari',
            'nis' => '3001',
            'unique_id' => 'STD-3001',
            'status' => 'aktif',
        ]);

        // Catatan presensi siswa
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'status' => 'tepat_waktu',
            'attendance_time' => '2026-10-12 07:15:00',
            'checkout_time' => '2026-10-12 15:30:00',
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        return compact('admin', 'schoolClass', 'student', 'teacher', 'attendance', 'academicYear', 'semester');
    }

    public function test_guest_is_redirected_to_login_when_accessing_admin_reports(): void
    {
        $response = $this->get(route('admin.reports.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_report_create_form(): void
    {
        $ctx = $this->setupAdminContext();

        $response = $this->actingAs($ctx['admin'])->get(route('admin.reports.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.create');
        $response->assertViewHas('classes');
        $response->assertViewHas('students');
    }

    public function test_admin_can_view_charts_page(): void
    {
        $ctx = $this->setupAdminContext();

        $response = $this->actingAs($ctx['admin'])->get(route('admin.reports.charts'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.charts');
        $response->assertViewHas('classes');
        $response->assertViewHas('students');
    }

    public function test_admin_can_retrieve_chart_data_for_class(): void
    {
        $ctx = $this->setupAdminContext();

        $response = $this->actingAs($ctx['admin'])->postJson(route('admin.reports.charts.data'), [
            'target_type' => 'class',
            'school_class_id' => $ctx['schoolClass']->id,
            'period_type' => 'month',
            'year' => 2026,
            'period_value' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'monthly',
            'summary' => ['hadir', 'sakit', 'izin', 'alpa'],
        ]);
    }

    public function test_admin_can_retrieve_chart_data_for_student(): void
    {
        $ctx = $this->setupAdminContext();

        $response = $this->actingAs($ctx['admin'])->postJson(route('admin.reports.charts.data'), [
            'target_type' => 'student',
            'student_id' => $ctx['student']->id,
            'period_type' => 'month',
            'year' => 2026,
            'period_value' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'monthly',
            'summary',
        ]);
    }

    public function test_admin_can_generate_class_monthly_pdf_report(): void
    {
        $ctx = $this->setupAdminContext();

        $response = $this->actingAs($ctx['admin'])->post(route('admin.reports.generate'), [
            'report_type' => 'class_monthly',
            'school_class_id' => $ctx['schoolClass']->id,
            'month' => '2026-10',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_generate_student_detailed_pdf_report(): void
    {
        $ctx = $this->setupAdminContext();

        $response = $this->actingAs($ctx['admin'])->post(route('admin.reports.generate'), [
            'report_type' => 'student_detailed',
            'student_id' => $ctx['student']->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_generate_school_lateness_pdf_report(): void
    {
        $ctx = $this->setupAdminContext();

        // Buat data presensi terlambat
        Attendance::create([
            'student_id' => $ctx['student']->id,
            'status' => 'terlambat',
            'attendance_time' => '2026-10-13 08:00:00',
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
        ]);

        $response = $this->actingAs($ctx['admin'])->post(route('admin.reports.generate'), [
            'report_type' => 'school_lateness',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_generate_no_checkout_pdf_report(): void
    {
        $ctx = $this->setupAdminContext();

        // Presensi tanpa checkout
        Attendance::create([
            'student_id' => $ctx['student']->id,
            'status' => 'tepat_waktu',
            'attendance_time' => '2026-10-14 07:10:00',
            'checkout_time' => null,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
        ]);

        $response = $this->actingAs($ctx['admin'])->post(route('admin.reports.generate'), [
            'report_type' => 'school_no_checkout',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }
}
