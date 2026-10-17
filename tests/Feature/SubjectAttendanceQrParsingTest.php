<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAttendance;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Teacher\SubjectAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectAttendanceQrParsingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupTeacherWithStudentAndSchedule(string $uuid = '550e8400-e29b-41d4-a716-446655440000', string $nis = '1001'): array
    {
        Carbon::setTestNow('2026-10-12 08:00:00');

        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru Matematika',
            'nip' => '198501012010011002',
        ]);

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

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);
        $subject = Subject::create(['name' => 'Matematika']);

        $student = Student::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Budi Santoso',
            'nis' => $nis,
            'unique_id' => $uuid,
            'status' => 'aktif',
        ]);

        $assignment = TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $schedule = Schedule::create([
            'teaching_assignment_id' => $assignment->id,
            'teacher_id' => $teacher->id,
            'school_class_id' => $schoolClass->id,
            'day_of_week' => 1, // Senin
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'schedule_type' => 'regular',
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        return compact('user', 'teacher', 'schoolClass', 'subject', 'student', 'assignment', 'schedule', 'academicYear', 'semester');
    }

    /**
     * Skenario A: Memindai QR Code dengan format UUID murni (36 karakter ber-hyphen).
     */
    public function test_scan_attendance_with_pure_uuid_qr_code_succeeds(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $ctx = $this->setupTeacherWithStudentAndSchedule(uuid: $uuid, nis: '1001');

        $service = app(SubjectAttendanceService::class);
        $result = $service->recordScanAttendance($ctx['schedule'], $ctx['teacher'], $uuid, '2026-10-12');

        $this->assertEquals(200, $result['status'], 'Status response harus 200 ketika scan menggunakan UUID valid');
        $this->assertTrue($result['payload']['success'], 'Payload success harus true');
        $this->assertEquals($ctx['student']->id, $result['payload']['student']['id']);

        $this->assertDatabaseHas('subject_attendances', [
            'schedule_id' => $ctx['schedule']->id,
            'student_id' => $ctx['student']->id,
            'teacher_id' => $ctx['teacher']->id,
            'status' => 'hadir',
        ]);
    }

    /**
     * Skenario B: Memindai QR Code dengan format gabungan {nis}-{uuid}.
     */
    public function test_scan_attendance_with_combined_nis_and_uuid_qr_code_succeeds(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $nis = '1001';
        $ctx = $this->setupTeacherWithStudentAndSchedule(uuid: $uuid, nis: $nis);

        $combinedQr = "{$nis}-{$uuid}";
        $service = app(SubjectAttendanceService::class);
        $result = $service->recordScanAttendance($ctx['schedule'], $ctx['teacher'], $combinedQr, '2026-10-12');

        $this->assertEquals(200, $result['status'], 'Status response harus 200 untuk format gabungan NIS-UUID');
        $this->assertTrue($result['payload']['success']);
        $this->assertEquals($ctx['student']->id, $result['payload']['student']['id']);
    }

    /**
     * Skenario C: Memindai QR Code yang tidak cocok dengan siswa mana pun.
     */
    public function test_scan_attendance_with_invalid_qr_code_is_rejected(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        $service = app(SubjectAttendanceService::class);
        $result = $service->recordScanAttendance($ctx['schedule'], $ctx['teacher'], 'non-existent-qr-code', '2026-10-12');

        $this->assertEquals(404, $result['status'], 'Status response harus 404 ketika QR tidak cocok');
        $this->assertFalse($result['payload']['success']);
        $this->assertStringContainsString('Siswa tidak ditemukan atau QR Code tidak valid', $result['payload']['message']);
    }

    /**
     * Integrasi Endpoint HTTP: Memindai via POST teacher.subject.attendance.store dengan UUID murni.
     */
    public function test_http_endpoint_scan_attendance_with_pure_uuid_succeeds(): void
    {
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $ctx = $this->setupTeacherWithStudentAndSchedule(uuid: $uuid, nis: '1002');

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.subject.attendance.store'), [
            'schedule_id' => $ctx['schedule']->id,
            'student_unique_id' => $uuid,
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'student' => [
                'id' => $ctx['student']->id,
                'status' => 'hadir',
            ]
        ]);
    }

    /**
     * Skenario Tambahan A: Memindai QR Code yang hanya berisi NIS siswa.
     */
    public function test_scan_attendance_with_plain_nis_qr_code_succeeds(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440003';
        $nis = '1003';
        $ctx = $this->setupTeacherWithStudentAndSchedule(uuid: $uuid, nis: $nis);

        // Siswa kedua di kelas yang sama untuk memastikan query tidak salah memilih siswa
        $otherStudent = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Siswa Lain',
            'nis' => '1004',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440004',
            'status' => 'aktif',
        ]);

        $service = app(SubjectAttendanceService::class);
        $result = $service->recordScanAttendance($ctx['schedule'], $ctx['teacher'], $nis, '2026-10-12');

        $this->assertEquals(200, $result['status'], 'Status response harus 200 untuk scan NIS saja');
        $this->assertTrue($result['payload']['success']);
        $this->assertEquals($ctx['student']->id, $result['payload']['student']['id']);

        $this->assertDatabaseHas('subject_attendances', [
            'schedule_id' => $ctx['schedule']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'hadir',
        ]);

        $this->assertDatabaseMissing('subject_attendances', [
            'schedule_id' => $ctx['schedule']->id,
            'student_id' => $otherStudent->id,
        ]);
    }

    /**
     * Skenario Tambahan B: Memindai format gabungan dengan NIS yang mengandung tanda hubung (misal: 10-01).
     */
    public function test_scan_attendance_with_hyphenated_nis_in_combined_format_succeeds(): void
    {
        $uuid = '7c9e6679-7425-40de-944b-e07fc1f90ae7';
        $hyphenatedNis = '10-01';
        $ctx = $this->setupTeacherWithStudentAndSchedule(uuid: $uuid, nis: $hyphenatedNis);

        $combinedQr = "{$hyphenatedNis}-{$uuid}";
        $service = app(SubjectAttendanceService::class);
        $result = $service->recordScanAttendance($ctx['schedule'], $ctx['teacher'], $combinedQr, '2026-10-12');

        $this->assertEquals(200, $result['status'], 'Status response harus 200 untuk NIS bertanda hubung dalam format gabungan');
        $this->assertTrue($result['payload']['success']);
        $this->assertEquals($ctx['student']->id, $result['payload']['student']['id']);

        $this->assertDatabaseHas('subject_attendances', [
            'schedule_id' => $ctx['schedule']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'hadir',
        ]);
    }
}
