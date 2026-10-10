<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Attendance;
use App\Models\StudentPermit;
use App\Models\Setting;
use App\Models\AcademicYear;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\RedirectToSipada;

class PermitAndStudentEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed base settings
        Setting::updateOrCreate(['key' => 'school_name'], ['value' => 'SMP Negeri 1 Biau']);
        Setting::updateOrCreate(['key' => 'school_latitude'], ['value' => '1.123456']);
        Setting::updateOrCreate(['key' => 'school_longitude'], ['value' => '121.123456']);
        Setting::updateOrCreate(['key' => 'attendance_radius'], ['value' => '500']);
        Setting::updateOrCreate(['key' => 'start_time'], ['value' => '07:15']);
    }

    public function test_guest_is_redirected_when_accessing_permit_scanner(): void
    {
        $response = $this->get(route('permit.scanner'));
        $response->assertRedirect(route('login'));
    }

    public function test_parent_cannot_access_permit_scanner(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parentUser)->get(route('permit.scanner'));
        // Parent cannot access permit scanner (redirected either by scanner.access or onboarding)
        $response->assertRedirect();
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_satpam_or_admin_can_access_permit_scanner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $satpam = User::factory()->create(['role' => 'satpam']);

        $this->actingAs($admin)->get(route('permit.scanner'))->assertOk();
        $this->actingAs($satpam)->get(route('permit.scanner'))->assertOk();
    }

    public function test_student_permit_checkout_and_return_preserves_on_time_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'name' => 'Ahmad Siswa',
            'nis' => '1001',
            'unique_id' => (string) Str::uuid(),
        ]);

        $today = Carbon::today();
        $clockInTime = Carbon::today()->setHour(7)->setMinute(0); // 07:00 (On-time)

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => $clockInTime,
            'status' => 'tepat_waktu',
        ]);

        // 1. Checkout on permit
        $responseOut = $this->actingAs($admin)->postJson(route('permit.store'), [
            'student_unique_id' => $student->unique_id,
            'reason' => 'Ke Rumah Sakit',
            'latitude' => 1.123456,
            'longitude' => 121.123456,
        ]);

        $responseOut->assertOk()
            ->assertJson(['status' => 'permit_granted']);

        $attendance->refresh();
        $this->assertEquals('izin_keluar', $attendance->status);
        $this->assertDatabaseHas('student_permits', [
            'student_id' => $student->id,
            'attendance_id' => $attendance->id,
            'reason' => 'Ke Rumah Sakit',
        ]);

        // 2. Return from permit
        $responseIn = $this->actingAs($admin)->postJson(route('permit.store'), [
            'student_unique_id' => $student->unique_id,
            'latitude' => 1.123456,
            'longitude' => 121.123456,
        ]);

        $responseIn->assertOk()
            ->assertJson(['status' => 'clock_in_from_permit']);

        $attendance->refresh();
        $this->assertEquals('tepat_waktu', $attendance->status);
    }

    public function test_student_permit_checkout_and_return_preserves_late_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'name' => 'Budi Terlambat',
            'nis' => '1002',
            'unique_id' => (string) Str::uuid(),
        ]);

        // Pagi datang jam 07:45 (Terlambat karena cutoff 07:15)
        $clockInTime = Carbon::today()->setHour(7)->setMinute(45);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => $clockInTime,
            'status' => 'terlambat',
        ]);

        // 1. Checkout on permit
        $this->actingAs($admin)->postJson(route('permit.store'), [
            'student_unique_id' => $student->unique_id,
            'reason' => 'Fotokopi modul',
            'latitude' => 1.123456,
            'longitude' => 121.123456,
        ])->assertOk();

        $attendance->refresh();
        $this->assertEquals('izin_keluar', $attendance->status);

        // 2. Return from permit -> WAJIB kembali menjadi 'terlambat', TIDAK boleh ter-reset jadi 'tepat_waktu'
        $responseIn = $this->actingAs($admin)->postJson(route('permit.store'), [
            'student_unique_id' => $student->unique_id,
            'latitude' => 1.123456,
            'longitude' => 121.123456,
        ]);

        $responseIn->assertOk()
            ->assertJson(['status' => 'clock_in_from_permit']);

        $attendance->refresh();
        $this->assertEquals('terlambat', $attendance->status);
    }

    public function test_admin_can_view_student_qr_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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
        $class = SchoolClass::create([
            'name' => '9-A',
            'academic_year_id' => $year->id,
            'semester_id' => $sem->id,
        ]);
        $student = Student::create([
            'name' => 'Dewi Sartika',
            'nis' => '9901',
            'school_class_id' => $class->id,
            'unique_id' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.qr'));

        $response->assertOk()
            ->assertSee('Dewi Sartika')
            ->assertSee('NIS: 9901')
            ->assertSee('Kelas 9-A');
    }

    public function test_admin_can_view_student_import_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // When sipada redirect is bypassed, import form is accessible
        $response = $this->actingAs($admin)
            ->withoutMiddleware(RedirectToSipada::class)
            ->get(route('admin.students.import.form'));

        $response->assertOk()
            ->assertSee('Impor Data Siswa dari Excel');
    }
}
