<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ParentModel;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Notification\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppNotificationGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupContext(): array
    {
        Carbon::setTestNow('2026-10-12 08:30:00'); // Senin

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
            'nip' => '198401012010011005',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $student = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '3001',
            'name' => 'Ahmad Santoso',
            'gender' => 'L',
            'unique_id' => (string) Str::uuid(),
            'status' => 'aktif',
        ]);

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Bapak Santoso',
            'phone_number' => '081234567890',
        ]);
        $student->parents()->attach($parent->id);

        return compact('admin', 'student', 'parent');
    }

    public function test_admin_can_view_whatsapp_settings_page(): void
    {
        $context = $this->setupContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.settings.whatsapp'));

        $response->assertOk()
            ->assertSee('Gateway WhatsApp & Notifikasi Wali Murid', false)
            ->assertSee('wa_gateway_provider', false)
            ->assertSee('wa_gateway_token', false)
            ->assertSee('Uji Coba Pengiriman Pesan');
    }

    public function test_admin_can_update_whatsapp_gateway_settings(): void
    {
        $context = $this->setupContext();

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.settings.update'), [
                'form_type' => 'whatsapp',
                'wa_gateway_provider' => 'fonnte',
                'wa_gateway_token' => 'secret-fonnte-token-123',
                'wa_notify_clock_in' => 'on',
                'wa_notify_clock_out' => 'on',
                'wa_notify_absent' => 'on',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('fonnte', Setting::where('key', 'wa_gateway_provider')->value('value'));
        $this->assertEquals('secret-fonnte-token-123', Setting::where('key', 'wa_gateway_token')->value('value'));
        $this->assertEquals('on', Setting::where('key', 'wa_notify_clock_in')->value('value'));
        $this->assertEquals('on', Setting::where('key', 'wa_notify_clock_out')->value('value'));
        $this->assertEquals('on', Setting::where('key', 'wa_notify_absent')->value('value'));
    }

    public function test_admin_can_test_whatsapp_gateway_in_log_mode(): void
    {
        $context = $this->setupContext();
        Setting::updateOrCreate(['key' => 'wa_gateway_provider'], ['value' => 'log']);

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.settings.whatsapp.test'), [
                'test_phone' => '081234567890',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_whatsapp_service_sends_via_fonnte(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true, 'id' => 'msg-123'], 200),
        ]);

        Setting::updateOrCreate(['key' => 'wa_gateway_provider'], ['value' => 'fonnte']);
        Setting::updateOrCreate(['key' => 'wa_gateway_token'], ['value' => 'token-fonnte-xyz']);

        $service = new WhatsAppNotificationService();
        $result = $service->sendMessage('081234567890', 'Pesan presensi siswa');

        $this->assertTrue($result['success']);
        $this->assertEquals('fonnte', $result['provider']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send' &&
                $request->hasHeader('Authorization', 'token-fonnte-xyz') &&
                $request['target'] === '6281234567890' &&
                $request['message'] === 'Pesan presensi siswa';
        });
    }

    public function test_whatsapp_service_sends_via_wablas(): void
    {
        Http::fake([
            'https://phone.wablas.com/api/send-message' => Http::response(['status' => 'success'], 200),
        ]);

        Setting::updateOrCreate(['key' => 'wa_gateway_provider'], ['value' => 'wablas']);
        Setting::updateOrCreate(['key' => 'wa_gateway_token'], ['value' => 'token-wablas-abc']);

        $service = new WhatsAppNotificationService();
        $result = $service->sendMessage('+62 812-9999-8888', 'Pesan absensi pulang');

        $this->assertTrue($result['success']);
        $this->assertEquals('wablas', $result['provider']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://phone.wablas.com/api/send-message' &&
                $request->hasHeader('Authorization', 'token-wablas-abc') &&
                $request['phone'] === '6281299998888';
        });
    }

    public function test_whatsapp_service_normalizes_phone_numbers_correctly(): void
    {
        $service = new WhatsAppNotificationService();

        $this->assertEquals('6281234567890', $service->normalizePhoneNumber('081234567890'));
        $this->assertEquals('6281234567890', $service->normalizePhoneNumber('+62 812-3456-7890'));
        $this->assertEquals('6281234567890', $service->normalizePhoneNumber('81234567890'));
        $this->assertEquals('6281234567890', $service->normalizePhoneNumber('6281234567890'));
        $this->assertNull($service->normalizePhoneNumber(''));
        $this->assertNull($service->normalizePhoneNumber(null));
    }

    public function test_clock_in_dispatches_whatsapp_notification_job_when_enabled(): void
    {
        Queue::fake();

        $context = $this->setupContext();
        Setting::updateOrCreate(['key' => 'school_latitude'], ['value' => '-6.200000']);
        Setting::updateOrCreate(['key' => 'school_longitude'], ['value' => '106.816666']);
        Setting::updateOrCreate(['key' => 'attendance_radius'], ['value' => '500']);
        Setting::updateOrCreate(['key' => 'jam_masuk'], ['value' => '07:30']);
        Setting::updateOrCreate(['key' => 'wa_notify_clock_in'], ['value' => 'on']);

        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['student']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => '2026-10-12 07:15:00',
            ]);

        $response->assertOk();

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) use ($context) {
            return $job->type === 'clock_in' && $job->studentId === $context['student']->id;
        });
    }

    public function test_clock_out_dispatches_whatsapp_notification_job_when_enabled(): void
    {
        Queue::fake();

        $context = $this->setupContext();
        Setting::updateOrCreate(['key' => 'school_latitude'], ['value' => '-6.200000']);
        Setting::updateOrCreate(['key' => 'school_longitude'], ['value' => '106.816666']);
        Setting::updateOrCreate(['key' => 'attendance_radius'], ['value' => '500']);
        Setting::updateOrCreate(['key' => 'jam_masuk'], ['value' => '07:30']);
        Setting::updateOrCreate(['key' => 'jam_pulang'], ['value' => '16:00']);
        Setting::updateOrCreate(['key' => 'wa_notify_clock_out'], ['value' => 'on']);

        // Siswa sudah absen masuk pagi
        $attendance = Attendance::create([
            'student_id' => $context['student']->id,
            'attendance_time' => Carbon::parse('2026-10-12 07:15:00'),
            'status' => 'tepat_waktu',
        ]);

        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['student']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => '2026-10-12 16:10:00',
            ]);

        $response->assertOk();

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) use ($context, $attendance) {
            return $job->type === 'clock_out' &&
                $job->studentId === $context['student']->id &&
                $job->attendanceId === $attendance->id;
        });
    }

    public function test_cutoff_absent_command_dispatches_whatsapp_notification_job_when_enabled(): void
    {
        Queue::fake();

        $context = $this->setupContext();
        Setting::updateOrCreate(['key' => 'wa_notify_absent'], ['value' => 'on']);

        $this->artisan('attendance:check-absent')
            ->assertExitCode(0);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) use ($context) {
            return $job->type === 'absent' && $job->studentId === $context['student']->id;
        });
    }

    public function test_job_handler_executes_service_notification_successfully(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $context = $this->setupContext();
        Setting::updateOrCreate(['key' => 'wa_gateway_provider'], ['value' => 'fonnte']);
        Setting::updateOrCreate(['key' => 'wa_gateway_token'], ['value' => 'test-token']);
        Setting::updateOrCreate(['key' => 'wa_notify_clock_in'], ['value' => 'on']);

        $attendance = Attendance::create([
            'student_id' => $context['student']->id,
            'attendance_time' => Carbon::parse('2026-10-12 07:15:00'),
            'status' => 'tepat_waktu',
        ]);

        $job = new SendWhatsAppNotificationJob('clock_in', $context['student']->id, $attendance->id);
        $job->handle(new WhatsAppNotificationService());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send' &&
                $request['target'] === '6281234567890' &&
                str_contains($request['message'], 'Informasi Kehadiran Siswa');
        });
    }
}
