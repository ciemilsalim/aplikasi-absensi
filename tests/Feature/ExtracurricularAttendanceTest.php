<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtracurricularAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupCoachWithExtracurricular(): array
    {
        Carbon::setTestNow('2026-10-12 15:00:00');

        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'name' => 'Pembina Pramuka',
            'nip' => '198701012011011005',
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
            'name' => 'VIII-B',
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $extracurricular = Extracurricular::create([
            'name' => 'Pramuka',
            'description' => 'Kegiatan Kepramukaan',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $extracurricular->teachers()->attach($teacher->id);

        $student = Student::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Ahmad Dahlan',
            'nis' => '2001',
            'unique_id' => 'STD-2001',
            'status' => 'aktif',
        ]);

        $extracurricular->students()->attach($student->id);

        return compact('user', 'teacher', 'extracurricular', 'student', 'schoolClass', 'academicYear', 'semester');
    }

    public function test_guest_is_redirected_to_login_when_accessing_extracurricular_attendance(): void
    {
        $response = $this->get('/teacher/extracurricular-attendance');
        $response->assertRedirect(route('login'));
    }

    public function test_teacher_can_view_extracurricular_attendance_index(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->get(route('teacher.extracurricular-attendance.index'));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.extracurricular_attendance.index');
        $response->assertViewHas('extracurriculars');
    }

    public function test_coach_can_view_create_page(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->get(route('teacher.extracurricular-attendance.create', $ctx['extracurricular']));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.extracurricular_attendance.create');
        $response->assertViewHas('extracurricular');
    }

    public function test_non_coach_cannot_view_create_page(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $otherUser = User::factory()->create(['role' => 'teacher']);
        Teacher::create([
            'user_id' => $otherUser->id,
            'name' => 'Guru Lain',
            'nip' => '199201012015011009',
        ]);

        $response = $this->actingAs($otherUser)->get(route('teacher.extracurricular-attendance.create', $ctx['extracurricular']));
        $response->assertRedirect(route('teacher.dashboard', ['view' => 'pembina_ekskul']));
        $response->assertSessionHas('error');
    }

    public function test_coach_can_store_manual_attendance_successfully(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->post(route('teacher.extracurricular-attendance.store', $ctx['extracurricular']), [
            'attendance_date' => '2026-10-12',
            'attendances' => [
                $ctx['student']->id => [
                    'status' => 'hadir',
                    'notes' => 'Latihan rutin tali-temali',
                ],
            ],
        ]);

        $response->assertRedirect(route('teacher.dashboard', ['view' => 'pembina_ekskul']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular']->id,
            'student_id' => $ctx['student']->id,
            'attendance_date' => '2026-10-12',
            'status' => 'hadir',
        ]);
    }

    public function test_coach_can_view_scanner(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->get(route('teacher.extracurricular-attendance.scanner', $ctx['extracurricular']));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.extracurricular_attendance.scanner');
    }

    public function test_coach_can_record_scan_attendance_successfully(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => 'STD-2001',
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

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'hadir',
        ]);
    }

    public function test_coach_cannot_record_scan_for_non_member_student(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        // Buat siswa lain yang bukan anggota ekskul
        $otherStudent = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Siswa Luar',
            'nis' => '9999',
            'unique_id' => 'STD-9999',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => 'STD-9999',
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_coach_can_mark_manual_attendance_and_reset(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        // 1. Mark status sakit
        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.mark_manual', $ctx['extracurricular']), [
            'student_id' => $ctx['student']->id,
            'status' => 'sakit',
            'date' => '2026-10-12',
            'notes' => 'Demam',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'sakit',
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'sakit',
        ]);

        // 2. Reset (hapus) status
        $resetResponse = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.mark_manual', $ctx['extracurricular']), [
            'student_id' => $ctx['student']->id,
            'status' => 'hapus',
            'date' => '2026-10-12',
        ]);

        $resetResponse->assertStatus(200);
        $resetResponse->assertJson([
            'success' => true,
            'status' => 'belum_absen',
        ]);

        $this->assertDatabaseMissing('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular']->id,
            'student_id' => $ctx['student']->id,
            'attendance_date' => '2026-10-12',
        ]);
    }

    public function test_coach_can_view_report_and_print(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        ExtracurricularAttendance::create([
            'extracurricular_id' => $ctx['extracurricular']->id,
            'student_id' => $ctx['student']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'attendance_date' => '2026-10-12',
            'status' => 'hadir',
        ]);

        // Report preview
        $reportResponse = $this->actingAs($ctx['user'])->get(route('teacher.extracurricular-attendance.report', [
            'extracurricular' => $ctx['extracurricular'],
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $reportResponse->assertStatus(200);
        $reportResponse->assertViewIs('teacher.extracurricular_attendance.report');

        // Print document
        $printResponse = $this->actingAs($ctx['user'])->get(route('teacher.extracurricular-attendance.print', [
            'extracurricular' => $ctx['extracurricular'],
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $printResponse->assertStatus(200);
        $printResponse->assertViewIs('teacher.extracurricular_attendance.print');
    }

    public function test_coach_can_record_scan_with_hyphenated_nis_in_combined_format(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $hyphenatedNis = '10-01';

        $student = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Siswa NIS Hyphen',
            'nis' => $hyphenatedNis,
            'unique_id' => $uuid,
            'status' => 'aktif',
        ]);
        $ctx['extracurricular']->students()->attach($student->id);

        $combinedQr = "{$hyphenatedNis}-{$uuid}";

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => $combinedQr,
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'status' => 'hadir',
            ]
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular']->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);
    }

    public function test_coach_can_record_scan_with_pure_uuid_qr_code(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $student = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Siswa Pure UUID',
            'nis' => '1002',
            'unique_id' => $uuid,
            'status' => 'aktif',
        ]);
        $ctx['extracurricular']->students()->attach($student->id);

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => $uuid,
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'status' => 'hadir',
            ]
        ]);
    }

    public function test_coach_can_record_scan_with_standard_combined_nis_and_uuid(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $uuid = '7c9e6679-7425-40de-944b-e07fc1f90ae7';
        $nis = '1003';
        $student = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Siswa Regular Combined',
            'nis' => $nis,
            'unique_id' => $uuid,
            'status' => 'aktif',
        ]);
        $ctx['extracurricular']->students()->attach($student->id);

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => "{$nis}-{$uuid}",
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'status' => 'hadir',
            ]
        ]);
    }

    public function test_coach_can_record_scan_with_plain_nis(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $nis = '1004';
        $student = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Siswa Plain NIS',
            'nis' => $nis,
            'unique_id' => '8c9e6679-7425-40de-944b-e07fc1f90ae8',
            'status' => 'aktif',
        ]);
        $ctx['extracurricular']->students()->attach($student->id);

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => $nis,
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'status' => 'hadir',
            ]
        ]);
    }

    public function test_coach_can_record_scan_with_plain_student_id(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => (string) $ctx['student']->id,
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

    public function test_coach_scan_with_invalid_qr_returns_404(): void
    {
        $ctx = $this->setupCoachWithExtracurricular();

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.extracurricular-attendance.scan', $ctx['extracurricular']), [
            'student_unique_id' => 'invalid-random-qr-code',
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Siswa tidak ditemukan atau QR Code tidak valid.',
        ]);
    }
}
