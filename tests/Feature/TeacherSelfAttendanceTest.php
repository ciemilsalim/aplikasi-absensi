<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherSelfAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupTeacherContext(): array
    {
        // Set fixed weekday (Monday)
        Carbon::setTestNow('2026-10-12 07:15:00');
        Storage::fake('public');

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

        // School coordinates: SMPN 1 Biau
        Setting::updateOrCreate(['key' => 'school_latitude'], ['value' => '1.17120000']);
        Setting::updateOrCreate(['key' => 'school_longitude'], ['value' => '121.43600000']);
        Setting::updateOrCreate(['key' => 'attendance_radius'], ['value' => '100']);
        Setting::updateOrCreate(['key' => 'max_gps_accuracy'], ['value' => '100']);

        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'name' => 'Budi Guru',
            'nip' => '198401012009011001',
        ]);

        return compact('user', 'teacher', 'academicYear', 'semester');
    }

    public function test_teacher_can_get_self_attendance_status(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $response = $this->getJson('/api/teacher/self-attendance/status');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'has_clocked_in' => false,
                'is_checked_out' => false,
            ]
        ]);
    }

    public function test_teacher_can_clock_in_with_valid_gps_and_good_accuracy(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        // Base64 1x1 png image
        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson('/api/teacher/self-attendance/clock-in', [
            'latitude' => 1.17120500, // within ~5 meters of school
            'longitude' => 121.43600500,
            'accuracy' => 12.5, // 12.5m high accuracy
            'is_mock_location' => false,
            'client_timestamp' => now()->timestamp,
            'photo' => $dummyPhoto,
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'success']);

        $this->assertDatabaseHas('teacher_attendances', [
            'teacher_id' => $context['teacher']->id,
            'status' => 'hadir',
            'is_mock_location' => false,
        ]);

        $attendance = TeacherAttendance::first();
        $this->assertEquals(12.5, $attendance->gps_accuracy);
        $this->assertNotNull($attendance->photo_evidence);
    }

    public function test_teacher_cannot_clock_in_when_out_of_school_radius(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Coordinates far away (~2000 meters)
        $response = $this->postJson('/api/teacher/self-attendance/clock-in', [
            'latitude' => 1.19000000,
            'longitude' => 121.45000000,
            'accuracy' => 15.0,
            'is_mock_location' => false,
            'photo' => $dummyPhoto,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('di luar jangkauan sekolah', $response->json('message'));
        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_teacher_clock_in_is_rejected_when_mock_location_is_detected(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Attempt clock in with fake GPS / mock location active
        $response = $this->postJson('/api/teacher/self-attendance/clock-in', [
            'latitude' => 1.17120000,
            'longitude' => 121.43600000,
            'accuracy' => 10.0,
            'is_mock_location' => true,
            'photo' => $dummyPhoto,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('Fake GPS / Mock Location', $response->json('message'));
        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_teacher_clock_in_is_rejected_when_gps_accuracy_is_too_poor(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // Accuracy is 350 meters (e.g. cellular tower estimate or poor signal)
        $response = $this->postJson('/api/teacher/self-attendance/clock-in', [
            'latitude' => 1.17120000,
            'longitude' => 121.43600000,
            'accuracy' => 350.0,
            'is_mock_location' => false,
            'photo' => $dummyPhoto,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('Sinyal GPS tidak akurat', $response->json('message'));
        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_teacher_clock_in_is_rejected_when_time_tampered(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        // 30 minutes in the future
        $fakeFutureTimestamp = now()->addMinutes(30)->timestamp;

        $response = $this->postJson('/api/teacher/self-attendance/clock-in', [
            'latitude' => 1.17120000,
            'longitude' => 121.43600000,
            'accuracy' => 15.0,
            'is_mock_location' => false,
            'client_timestamp' => $fakeFutureTimestamp,
            'photo' => $dummyPhoto,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['status' => 'error']);
        $this->assertStringContainsString('tidak sesuai dengan waktu server', $response->json('message'));
        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_teacher_can_clock_out_successfully(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $attendance = TeacherAttendance::create([
            'teacher_id' => $context['teacher']->id,
            'status' => 'hadir',
            'latitude' => 1.17120000,
            'longitude' => 121.43600000,
            'gps_accuracy' => 10.0,
            'is_mock_location' => false,
            'created_at' => Carbon::parse('2026-10-12 07:15:00'),
        ]);

        // Advance time to 14:30
        Carbon::setTestNow('2026-10-12 14:30:00');

        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson('/api/teacher/self-attendance/clock-out', [
            'latitude' => 1.17120000,
            'longitude' => 121.43600000,
            'accuracy' => 14.2,
            'is_mock_location' => false,
            'photo' => $dummyPhoto,
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'success']);

        $attendance->refresh();
        $this->assertNotNull($attendance->checkout_time);
        $this->assertEquals(14.2, $attendance->checkout_gps_accuracy);
        $this->assertFalse((bool) $attendance->checkout_is_mock_location);
    }

    public function test_teacher_can_register_face_and_view_history(): void
    {
        $context = $this->setupTeacherContext();
        Sanctum::actingAs($context['user']);

        $dummyPhoto = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $faceResponse = $this->postJson('/api/teacher/self-attendance/register-face', [
            'photo' => $dummyPhoto,
        ]);

        $faceResponse->assertOk();
        $context['teacher']->refresh();
        $this->assertNotNull($context['teacher']->photo);

        $historyResponse = $this->getJson('/api/teacher/self-attendance/history');
        $historyResponse->assertOk();
        $historyResponse->assertJsonStructure([
            'status',
            'data'
        ]);
    }
}
