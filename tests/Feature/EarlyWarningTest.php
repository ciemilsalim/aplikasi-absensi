<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ParentModel;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Attendance\EarlyWarningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupEarlyWarningContext(): array
    {
        Carbon::setTestNow('2026-10-12 09:00:00');

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
            'name' => 'Wali Kelas VIII-A',
            'nip' => '198101012006041001',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VIII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        // Student 1: High Risk (3x Alpa)
        $studentCritical = Student::create([
            'nis' => '8001',
            'nisn' => '0080011111',
            'name' => 'Fathir Kritis',
            'school_class_id' => $schoolClass->id,
            'gender' => 'L',
            'status' => 'aktif',
        ]);

        // Student 2: Medium Risk (3x Terlambat)
        $studentWarning = Student::create([
            'nis' => '8002',
            'nisn' => '0080022222',
            'name' => 'Dini Waspada',
            'school_class_id' => $schoolClass->id,
            'gender' => 'P',
            'status' => 'aktif',
        ]);

        // Student 3: Good Attendance
        $studentGood = Student::create([
            'nis' => '8003',
            'nisn' => '0080033333',
            'name' => 'Bagus Rajin',
            'school_class_id' => $schoolClass->id,
            'gender' => 'L',
            'status' => 'aktif',
        ]);

        // Parent for student 1 with phone
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Ayah Fathir',
            'phone_number' => '081234567890',
        ]);
        $studentCritical->parents()->attach($parent->id);

        // Attendance records for studentCritical (3 Alpa in last 10 days)
        for ($i = 1; $i <= 3; $i++) {
            Attendance::create([
                'student_id' => $studentCritical->id,
                'status' => 'alpa',
                'attendance_time' => Carbon::parse("2026-10-0{$i} 07:00:00"),
            ]);
        }

        // Attendance records for studentWarning (3 Terlambat)
        for ($i = 1; $i <= 3; $i++) {
            Attendance::create([
                'student_id' => $studentWarning->id,
                'status' => 'terlambat',
                'attendance_time' => Carbon::parse("2026-10-0{$i} 07:45:00"),
            ]);
        }

        // Attendance records for studentGood (Hadir tepat waktu)
        for ($i = 1; $i <= 5; $i++) {
            Attendance::create([
                'student_id' => $studentGood->id,
                'status' => 'tepat_waktu',
                'attendance_time' => Carbon::parse("2026-10-0{$i} 06:45:00"),
            ]);
        }

        return compact('admin', 'teacherUser', 'teacher', 'schoolClass', 'studentCritical', 'studentWarning', 'studentGood');
    }

    public function test_early_warning_service_classifies_risk_and_sorts_by_severity(): void
    {
        $context = $this->setupEarlyWarningContext();
        $service = app(EarlyWarningService::class);

        $results = $service->getAttentionStudents(['days' => 30]);

        $this->assertCount(2, $results);

        $first = $results->first();
        $this->assertEquals($context['studentCritical']->id, $first->id);
        $this->assertEquals('high', $first->risk_level);
        $this->assertEquals(3, $first->alpha_count);
        $this->assertNotNull($first->wa_link);
        $this->assertStringContainsString('wa.me/6281234567890', $first->wa_link);

        $second = $results->last();
        $this->assertEquals($context['studentWarning']->id, $second->id);
        $this->assertEquals('medium', $second->risk_level);
        $this->assertEquals(3, $second->late_count);

        $summary = $service->getEarlyWarningSummary($results);
        $this->assertEquals(1, $summary['highRiskCount']);
        $this->assertEquals(1, $summary['mediumRiskCount']);
        $this->assertEquals(3, $summary['totalAlpaAccumulated']);
        $this->assertEquals(3, $summary['totalLateAccumulated']);
    }

    public function test_admin_dashboard_renders_early_warning_widget(): void
    {
        $context = $this->setupEarlyWarningContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHasAll(['attentionStudents', 'earlyWarningSummary']);
        $response->assertSee('Deteksi Dini Siswa Butuh Perhatian');
        $response->assertSee('Fathir Kritis');
        $response->assertSee('Dini Waspada');
        $response->assertDontSee('Bagus Rajin');
    }

    public function test_homeroom_teacher_dashboard_renders_early_warning_widget(): void
    {
        $context = $this->setupEarlyWarningContext();

        $response = $this->actingAs($context['teacherUser'])
            ->get(route('teacher.dashboard'));

        $response->assertOk();
        $response->assertSee('Siswa Perlu Perhatian');
        $response->assertSee('Fathir Kritis');
    }
}
