<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ScanLog;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KioskOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupKioskContext(): array
    {
        Carbon::setTestNow('2026-10-12 09:30:00'); // Senin, jam 09:30

        $scannerUser = User::factory()->create([
            'role' => 'operator',
            'name' => 'Petugas Kiosk',
        ]);

        Setting::updateOrCreate(['key' => 'school_latitude'], ['value' => '-6.200000']);
        Setting::updateOrCreate(['key' => 'school_longitude'], ['value' => '106.816666']);
        Setting::updateOrCreate(['key' => 'attendance_radius'], ['value' => '500']);
        Setting::updateOrCreate(['key' => 'jam_masuk'], ['value' => '07:30']);
        Setting::updateOrCreate(['key' => 'jam_pulang'], ['value' => '16:00']);

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

        $studentA = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '1001',
            'name' => 'Budi Pratama',
            'gender' => 'L',
            'unique_id' => (string) Str::uuid(),
            'status' => 'aktif',
        ]);

        $studentB = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '1002',
            'name' => 'Siti Rahma',
            'gender' => 'P',
            'unique_id' => (string) Str::uuid(),
            'status' => 'aktif',
        ]);

        return compact('scannerUser', 'studentA', 'studentB');
    }

    public function test_kiosk_store_attendance_with_scanned_at_honors_on_time_status(): void
    {
        $context = $this->setupKioskContext();

        // Server sekarang jam 09:30 (jika tanpa scanned_at akan terlambat)
        // Tapi kiosk offline memindai pada jam 07:15
        $offlineScanTime = '2026-10-12 07:15:00';

        $response = $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['studentA']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => $offlineScanTime,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'clock_in',
                'attendance_status' => 'tepat_waktu',
                'student_name' => 'Budi Pratama',
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentA']->id,
            'status' => 'tepat_waktu',
        ]);

        $this->assertDatabaseHas('scan_logs', [
            'student_id' => $context['studentA']->id,
            'scan_type' => 'masuk',
            'scanned_at' => $offlineScanTime,
        ]);
    }

    public function test_kiosk_store_attendance_with_late_scanned_at_marks_terlambat(): void
    {
        $context = $this->setupKioskContext();

        // Kiosk offline memindai pada jam 07:45 (lewat batas 07:30)
        $lateOfflineScanTime = '2026-10-12 07:45:00';

        $response = $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['studentA']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => $lateOfflineScanTime,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'clock_in',
                'attendance_status' => 'terlambat',
                'student_name' => 'Budi Pratama',
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentA']->id,
            'status' => 'terlambat',
        ]);
    }

    public function test_kiosk_sync_offline_processes_batch_scans_correctly(): void
    {
        $context = $this->setupKioskContext();

        $batchPayload = [
            'scans' => [
                [
                    'client_uuid' => 'client-item-1',
                    'student_unique_id' => $context['studentA']->unique_id,
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'scanned_at' => '2026-10-12 07:10:00',
                ],
                [
                    'client_uuid' => 'client-item-2',
                    'student_unique_id' => $context['studentB']->unique_id,
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'scanned_at' => '2026-10-12 07:40:00',
                ],
                [
                    'client_uuid' => 'client-item-3',
                    'student_unique_id' => 'NON-EXISTENT-UUID-9999',
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'scanned_at' => '2026-10-12 07:20:00',
                ],
            ],
        ];

        $response = $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.sync_offline'), $batchPayload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'synced_count' => 2,
                'failed_count' => 1,
                'total' => 3,
            ]);

        // Student A tercatat tepat waktu
        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentA']->id,
            'status' => 'tepat_waktu',
        ]);

        // Student B tercatat terlambat
        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentB']->id,
            'status' => 'terlambat',
        ]);

        // Siswa tidak ada tetap tercatat di scan_logs sebagai gagal
        $this->assertDatabaseHas('scan_logs', [
            'scan_type' => 'gagal',
            'failure_reason' => 'Siswa tidak ditemukan (NON-EXISTENT-UUID-9999)',
        ]);
    }

    public function test_kiosk_sync_offline_handles_clock_out(): void
    {
        $context = $this->setupKioskContext();

        // Siswa A sudah absen masuk pagi tadi
        Attendance::create([
            'student_id' => $context['studentA']->id,
            'attendance_time' => Carbon::parse('2026-10-12 07:15:00'),
            'status' => 'tepat_waktu',
        ]);

        // Sync batch scan pulang pada 16:15
        $batchPayload = [
            'scans' => [
                [
                    'client_uuid' => 'client-checkout-1',
                    'student_unique_id' => $context['studentA']->unique_id,
                    'latitude' => -6.200000,
                    'longitude' => 106.816666,
                    'scanned_at' => '2026-10-12 16:15:00',
                ],
            ],
        ];

        $response = $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.sync_offline'), $batchPayload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'synced_count' => 1,
                'failed_count' => 0,
            ]);

        $attendance = Attendance::where('student_id', $context['studentA']->id)->first();
        $this->assertNotNull($attendance->checkout_time);
        $this->assertEquals('16:15:00', Carbon::parse($attendance->checkout_time)->format('H:i:s'));

        $this->assertDatabaseHas('scan_logs', [
            'student_id' => $context['studentA']->id,
            'scan_type' => 'pulang',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_sync_offline(): void
    {
        $response = $this->postJson(route('attendance.sync_offline'), [
            'scans' => [
                [
                    'student_unique_id' => 'dummy',
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                ],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function test_browser_iso_utc_scanned_at_converts_to_local_timezone_and_marks_tepat_waktu(): void
    {
        $context = $this->setupKioskContext();

        // Di WITA (Asia/Makassar, UTC+8):
        // Jam 07:15 WITA tanggal 2026-10-12 setara dengan 2026-10-11T23:15:00.000Z di browser
        $browserUtcIso = '2026-10-11T23:15:00.000Z';

        $response = $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['studentA']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => $browserUtcIso,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'clock_in',
                'attendance_status' => 'tepat_waktu',
                'student_name' => 'Budi Pratama',
            ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentA']->id,
            'status' => 'tepat_waktu',
        ]);
    }

    public function test_immediate_rescan_within_cooldown_period_does_not_trigger_clock_out(): void
    {
        $context = $this->setupKioskContext();

        // Siswa absen masuk jam 07:15
        $firstScanTime = '2026-10-12 07:15:00';
        $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['studentA']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => $firstScanTime,
            ])->assertOk();

        // Siswa memindai lagi 2 menit kemudian (07:17)
        // Sekalipun jam_pulang diatur pagi hari, proteksi jeda minimal harus mencegah clock_out
        Setting::updateOrCreate(['key' => 'jam_pulang'], ['value' => '07:00']);

        $secondResponse = $this->actingAs($context['scannerUser'])
            ->postJson(route('attendance.store'), [
                'student_unique_id' => $context['studentA']->unique_id,
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'scanned_at' => '2026-10-12 07:17:00',
            ]);

        $secondResponse->assertStatus(409)
            ->assertJson([
                'status' => 'already_clocked_in',
            ]);

        $attendance = Attendance::where('student_id', $context['studentA']->id)->first();
        $this->assertNull($attendance->checkout_time, 'Checkout time must remain NULL within cooldown period');
    }

    public function test_authorized_user_can_render_kiosk_scanner_view(): void
    {
        $context = $this->setupKioskContext();

        $response = $this->actingAs($context['scannerUser'])
            ->get(route('scanner'));

        $response->assertOk()
            ->assertSee('Pemindai Absensi Siswa')
            ->assertSee('network-status-badge', false)
            ->assertSee('offline-queue-container', false)
            ->assertSee('btn-sync-offline', false)
            ->assertSee('SiasekKioskDB', false);
    }
}


