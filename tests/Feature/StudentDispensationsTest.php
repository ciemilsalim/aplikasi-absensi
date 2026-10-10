<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\StudentPermit;
use App\Models\Attendance;
use App\Models\Setting;
use App\Jobs\SendWhatsAppNotificationJob;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentDispensationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'school_name'], ['value' => 'SMP Negeri 1 Biau']);
    }

    public function test_admin_and_staff_can_view_dispensations_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = SchoolClass::create(['name' => 'VIII-A']);
        $student = Student::create([
            'name' => 'Budi Santoso',
            'nis' => '9001',
            'unique_id' => (string) Str::uuid(),
            'school_class_id' => $class->id,
        ]);

        $permit = StudentPermit::create([
            'student_id' => $student->id,
            'reason' => 'Demam tinggi di UKS',
            'permit_type' => 'pulang_cepat',
            'pass_code' => 'PASS-TEST01',
            'approved_by_user_id' => $admin->id,
            'approved_by_name' => $admin->name,
            'status' => 'approved',
            'checkout_time' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dispensations.index'));

        $response->assertOk()
            ->assertSee('E-Dispensasi &amp; Pass Card Gerbang Siswa', false)
            ->assertSee('PASS-TEST01')
            ->assertSee('Budi Santoso')
            ->assertSee('Demam tinggi di UKS');
    }

    public function test_can_issue_e_dispensation_with_pass_code_and_notification(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Guru Piket Ani']);
        $class = SchoolClass::create(['name' => 'IX-B']);
        $student = Student::create([
            'name' => 'Siti Aisyah',
            'nis' => '9002',
            'unique_id' => (string) Str::uuid(),
            'school_class_id' => $class->id,
        ]);
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = \App\Models\ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Wali Siti',
            'phone_number' => '081234567890',
        ]);
        $student->parents()->attach($parent->id);

        $response = $this->actingAs($admin)->post(route('admin.dispensations.store'), [
            'student_id' => $student->id,
            'permit_type' => 'pulang_cepat',
            'reason' => 'Dipulangkan karena flu berat',
            'valid_until' => '12:00',
            'notify_parent' => 1,
            'notes' => 'Dijemput orang tua',
        ]);

        $response->assertRedirect(route('admin.dispensations.index'));
        $response->assertSessionHas('success');

        $permit = StudentPermit::where('student_id', $student->id)->latest()->first();
        $this->assertNotNull($permit);
        $this->assertStringStartsWith('PASS-', $permit->pass_code);
        $this->assertEquals('pulang_cepat', $permit->permit_type);
        $this->assertEquals('Dipulangkan karena flu berat', $permit->reason);
        $this->assertEquals($admin->id, $permit->approved_by_user_id);
        $this->assertEquals('Guru Piket Ani', $permit->approved_by_name);
        $this->assertEquals('Dijemput orang tua', $permit->notes);

        Queue::assertPushed(SendWhatsAppNotificationJob::class);
    }

    public function test_security_guard_can_scan_e_dispensation_at_gate(): void
    {
        Queue::fake();

        $security = User::factory()->create(['role' => 'satpam', 'name' => 'Satpam Pak Joko']);
        $class = SchoolClass::create(['name' => 'VII-C']);
        $student = Student::create([
            'name' => 'Rian Pratama',
            'nis' => '9003',
            'unique_id' => (string) Str::uuid(),
            'school_class_id' => $class->id,
        ]);
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = \App\Models\ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Wali Rian',
            'phone_number' => '081298765432',
        ]);
        $student->parents()->attach($parent->id);

        // Pre-approved dispensation
        $permit = StudentPermit::create([
            'student_id' => $student->id,
            'reason' => 'Dispensasi perwakilan OSIS',
            'permit_type' => 'tugas_luar',
            'pass_code' => 'PASS-OSIS01',
            'approved_by_name' => 'Pembina OSIS',
            'status' => 'pending_approval',
        ]);

        // Satpam scans pass code at gate scanner
        $response = $this->actingAs($security)->post(route('permit.store'), [
            'qr_data' => 'PASS-OSIS01',
        ]);

        $response->assertJson([
            'success' => true,
            'action' => 'checkout',
            'is_dispensation' => true,
        ]);

        $permit->refresh();
        $this->assertEquals('approved', $permit->status);
        $this->assertNotNull($permit->checkout_time);
        $this->assertEquals($security->id, $permit->security_user_id);
        $this->assertEquals('Satpam Pak Joko', $permit->security_name);

        Queue::assertPushed(SendWhatsAppNotificationJob::class);
    }

    public function test_returning_student_can_checkin_and_complete_permit(): void
    {
        Queue::fake();

        $security = User::factory()->create(['role' => 'satpam', 'name' => 'Satpam Pak Joko']);
        $student = Student::create([
            'name' => 'Ahmad Fauzi',
            'nis' => '9004',
            'unique_id' => (string) Str::uuid(),
        ]);

        // Siswa sudah hadir pagi tepat waktu
        Attendance::create([
            'student_id' => $student->id,
            'status' => 'tepat_waktu',
            'attendance_time' => Carbon::today()->setTime(6, 55, 0),
        ]);

        // Keluar sementara
        $permit = StudentPermit::create([
            'student_id' => $student->id,
            'reason' => 'Fotokopi berkas dinas',
            'permit_type' => 'keluar_sementara',
            'pass_code' => 'PASS-COPY01',
            'status' => 'approved',
            'checkout_time' => Carbon::now()->subMinutes(30),
            'approved_by_name' => 'Guru Piket',
        ]);

        // Siswa kembali dan scan lagi di gerbang
        $response = $this->actingAs($security)->post(route('permit.store'), [
            'qr_data' => $student->unique_id,
        ]);

        $response->assertJson([
            'success' => true,
            'action' => 'checkin',
        ]);

        $permit->refresh();
        $this->assertEquals('completed', $permit->status);
        $this->assertNotNull($permit->checkin_time);

        // Presensi pagi tidak hilang
        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('attendance_time', Carbon::today())
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('tepat_waktu', $attendance->status);
    }

    public function test_print_pass_card_renders_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'name' => 'Dinda Kirana',
            'nis' => '9005',
            'unique_id' => (string) Str::uuid(),
        ]);

        $permit = StudentPermit::create([
            'student_id' => $student->id,
            'reason' => 'Mengikuti lomba olimpiade sains',
            'permit_type' => 'dispensasi_lomba',
            'pass_code' => 'PASS-SAINS1',
            'approved_by_name' => 'Koordinator Lomba',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dispensations.print', $permit->id));

        $response->assertOk()
            ->assertSee('PASS-SAINS1')
            ->assertSee('Dinda Kirana')
            ->assertSee('Mengikuti lomba olimpiade sains')
            ->assertSee('SURAT IZIN KELUAR / PASS CARD');
    }

    public function test_authorized_user_can_cancel_dispensation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create([
            'name' => 'Kevin Sanjaya',
            'nis' => '9006',
            'unique_id' => (string) Str::uuid(),
        ]);

        $permit = StudentPermit::create([
            'student_id' => $student->id,
            'reason' => 'Salah input',
            'permit_type' => 'keluar_sementara',
            'pass_code' => 'PASS-CANCEL1',
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.dispensations.destroy', $permit->id));

        $response->assertRedirect(route('admin.dispensations.index'));
        $permit->refresh();
        $this->assertEquals('cancelled', $permit->status);
    }
}
