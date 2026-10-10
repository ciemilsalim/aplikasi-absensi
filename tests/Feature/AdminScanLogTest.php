<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ScanLog;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScanLogTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupScanLogContext(): array
    {
        Carbon::setTestNow('2026-10-12 08:30:00');

        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['role' => 'admin', 'name' => 'Petugas Piket']);

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
            'nip' => '198401012010011005',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $student = Student::create([
            'nis' => '1001',
            'nisn' => '0012345678',
            'name' => 'Budi Pratama',
            'school_class_id' => $schoolClass->id,
            'gender' => 'L',
            'status' => 'aktif',
        ]);

        $logMasuk = ScanLog::create([
            'user_id' => $operator->id,
            'user_name' => $operator->name,
            'user_role' => 'admin',
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_nis' => $student->nis,
            'scan_type' => 'masuk',
            'scanned_at' => Carbon::parse('2026-10-12 06:45:00'),
        ]);

        $logPulang = ScanLog::create([
            'user_id' => $operator->id,
            'user_name' => $operator->name,
            'user_role' => 'admin',
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_nis' => $student->nis,
            'scan_type' => 'pulang',
            'scanned_at' => Carbon::parse('2026-10-12 14:15:00'),
        ]);

        $logGagal = ScanLog::create([
            'user_id' => $operator->id,
            'user_name' => $operator->name,
            'user_role' => 'admin',
            'student_id' => null,
            'student_name' => 'Tidak Dikenal',
            'student_nis' => '-',
            'scan_type' => 'gagal',
            'failure_reason' => 'Wajah tidak cocok dengan database',
            'scanned_at' => Carbon::parse('2026-10-12 07:10:00'),
        ]);

        return compact('admin', 'operator', 'student', 'schoolClass', 'logMasuk', 'logPulang', 'logGagal');
    }

    public function test_admin_can_view_scan_logs_index_with_statistics(): void
    {
        $context = $this->setupScanLogContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.scan-logs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.scan-logs.index');
        $response->assertViewHasAll([
            'logs',
            'dateFrom',
            'dateTo',
            'todayStats',
            'scannerUsers',
            'topScanners',
            'perPage',
        ]);

        $todayStats = $response->viewData('todayStats');
        $this->assertEquals(3, $todayStats->total);
        $this->assertEquals(1, $todayStats->total_masuk);
        $this->assertEquals(1, $todayStats->total_pulang);
        $this->assertEquals(1, $todayStats->total_gagal);
    }

    public function test_admin_can_filter_scan_logs_by_type_and_search(): void
    {
        $context = $this->setupScanLogContext();

        // Filter scan masuk
        $response = $this->actingAs($context['admin'])
            ->get(route('admin.scan-logs.index', [
                'scan_type' => 'masuk',
            ]));

        $response->assertOk();
        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertEquals('masuk', $logs->first()->scan_type);

        // Filter search Budi
        $searchResponse = $this->actingAs($context['admin'])
            ->get(route('admin.scan-logs.index', [
                'search' => 'Budi Pratama',
            ]));

        $searchResponse->assertOk();
        $searchLogs = $searchResponse->viewData('logs');
        $this->assertCount(2, $searchLogs); // masuk and pulang
    }

    public function test_admin_can_filter_scan_logs_by_operator_user(): void
    {
        $context = $this->setupScanLogContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.scan-logs.index', [
                'user_id' => $context['operator']->id,
            ]));

        $response->assertOk();
        $logs = $response->viewData('logs');
        $this->assertCount(3, $logs);
    }
}
