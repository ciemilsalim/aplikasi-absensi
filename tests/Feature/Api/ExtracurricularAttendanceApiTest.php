<?php

namespace Tests\Feature\Api;

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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExtracurricularAttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Menyiapkan konteks kegiatan ekstrakurikuler, pembina sah, pembina lain, dan siswa anggota.
     */
    protected function setupExtracurricularContext(): array
    {
        Carbon::setTestNow('2026-10-12 16:00:00');

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
            'name' => 'VIII-A',
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $student = Student::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Ahmad Siswa',
            'nis' => '1001',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440001',
            'status' => 'aktif',
        ]);

        // Pembina Sah Ekskul 1 (Pramuka)
        $userCoach1 = User::factory()->create(['role' => 'teacher']);
        $teacherCoach1 = Teacher::create([
            'user_id' => $userCoach1->id,
            'name' => 'Pembina Pramuka',
            'nip' => '198001012005011001',
        ]);

        $extracurricular1 = Extracurricular::create([
            'name' => 'Pramuka',
            'description' => 'Kegiatan Kepramukaan',
            'teacher_id' => $teacherCoach1->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);
        $extracurricular1->teachers()->attach($teacherCoach1->id);
        $extracurricular1->students()->attach($student->id);

        // Guru Lain / Pembina Ekskul 2 (PMR)
        $userCoach2 = User::factory()->create(['role' => 'teacher']);
        $teacherCoach2 = Teacher::create([
            'user_id' => $userCoach2->id,
            'name' => 'Pembina PMR',
            'nip' => '198501012010011002',
        ]);

        $extracurricular2 = Extracurricular::create([
            'name' => 'PMR',
            'description' => 'Palang Merah Remaja',
            'teacher_id' => $teacherCoach2->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);
        $extracurricular2->teachers()->attach($teacherCoach2->id);

        return compact(
            'academicYear',
            'semester',
            'schoolClass',
            'student',
            'userCoach1',
            'teacherCoach1',
            'extracurricular1',
            'userCoach2',
            'teacherCoach2',
            'extracurricular2'
        );
    }

    /**
     * Skenario A — Pengguna Tanpa Profil Guru:
     * User terautentikasi tanpa relasi Teacher tidak boleh menyebabkan crash 500,
     * melainkan ditolak dengan HTTP 403 Forbidden.
     */
    public function test_store_attendance_by_user_without_teacher_profile_is_forbidden(): void
    {
        $ctx = $this->setupExtracurricularContext();

        $userWithoutTeacher = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($userWithoutTeacher);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'hadir',
                ]
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Anda bukan guru',
        ]);

        $this->assertDatabaseMissing('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $ctx['student']->id,
            'attendance_date' => '2026-10-12',
        ]);
    }

    /**
     * Skenario B — Guru Bukan Pembina (Proteksi BOLA / IDOR):
     * Guru valid yang bukan pembina kegiatan ekstrakurikuler yang diminta
     * harus ditolak dengan HTTP 403 dan tidak dapat memanipulasi presensi.
     * Memverifikasi secara eksplisit bahwa record yang sudah ada tidak berubah (immutability).
     */
    public function test_store_attendance_by_teacher_who_is_not_coach_is_forbidden(): void
    {
        $ctx = $this->setupExtracurricularContext();

        // 1. Siapkan record presensi awal yang valid dengan status 'izin'
        $initialAttendance = ExtracurricularAttendance::create([
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $ctx['student']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'attendance_date' => '2026-10-12',
            'status' => 'izin',
            'notes' => 'Izin kegiatan keluarga',
        ]);

        $initialCount = ExtracurricularAttendance::count();

        // 2. UserCoach2 (Pembina PMR / bukan pembina Ekskul 1) mencoba mengubah absensi menjadi 'hadir'
        Sanctum::actingAs($ctx['userCoach2']);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'hadir',
                ]
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('bukan pembina', $response->json('message'));

        // 3. Verifikasi integritas data: record awal tidak berubah sama sekali (immutability)
        $reloaded = $initialAttendance->fresh();
        $this->assertNotNull($reloaded);
        $this->assertEquals('izin', $reloaded->status);
        $this->assertEquals($ctx['extracurricular1']->id, $reloaded->extracurricular_id);
        $this->assertEquals($ctx['student']->id, $reloaded->student_id);
        $this->assertEquals('2026-10-12', substr((string)$reloaded->attendance_date, 0, 10));
        $this->assertEquals($ctx['academicYear']->id, $reloaded->academic_year_id);
        $this->assertEquals($ctx['semester']->id, $reloaded->semester_id);
        $this->assertEquals('Izin kegiatan keluarga', $reloaded->notes);

        // 4. Pastikan jumlah record presensi di database tidak bertambah
        $this->assertEquals($initialCount, ExtracurricularAttendance::count());
    }

    /**
     * Skenario C — Pembina Resmi (Happy Path):
     * Pembina sah ekstrakurikuler berhasil mencatat presensi dan tersimpan di database.
     */
    public function test_store_attendance_by_authorized_coach_succeeds(): void
    {
        $ctx = $this->setupExtracurricularContext();

        Sanctum::actingAs($ctx['userCoach1']);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'hadir',
                ]
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'hadir',
            'attendance_date' => '2026-10-12',
        ]);
    }

    /**
     * Skenario D — Batas Antar-Kegiatan (Cross-Activity Isolation):
     * Pembina Ekskul 1 mencoba mencatat presensi pada Ekskul 2 yang bukan kewenangannya.
     * Memverifikasi secara eksplisit bahwa record awal kegiatan B tidak berubah.
     */
    public function test_store_attendance_cross_activity_isolation_is_enforced(): void
    {
        $ctx = $this->setupExtracurricularContext();

        // 1. Siapkan record presensi awal untuk Ekskul 2 (PMR) dengan status 'sakit'
        $initialAttendance = ExtracurricularAttendance::create([
            'extracurricular_id' => $ctx['extracurricular2']->id,
            'student_id' => $ctx['student']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'attendance_date' => '2026-10-12',
            'status' => 'sakit',
            'notes' => 'Catatan awal PMR',
        ]);

        $initialCount = ExtracurricularAttendance::count();

        // 2. UserCoach1 (Pembina Ekskul 1) mencoba mengubah absensi pada Ekskul 2 (PMR) menjadi 'hadir'
        Sanctum::actingAs($ctx['userCoach1']);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular2']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'hadir',
                ]
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
        ]);

        // 3. Verifikasi record awal Ekskul 2 tetap utuh dan tidak termutasi
        $reloaded = $initialAttendance->fresh();
        $this->assertNotNull($reloaded);
        $this->assertEquals('sakit', $reloaded->status);
        $this->assertEquals($ctx['extracurricular2']->id, $reloaded->extracurricular_id);
        $this->assertEquals($ctx['student']->id, $reloaded->student_id);
        $this->assertEquals('2026-10-12', substr((string)$reloaded->attendance_date, 0, 10));
        $this->assertEquals($ctx['academicYear']->id, $reloaded->academic_year_id);
        $this->assertEquals($ctx['semester']->id, $reloaded->semester_id);
        $this->assertEquals('Catatan awal PMR', $reloaded->notes);

        // 4. Pastikan jumlah record presensi di database tidak bertambah
        $this->assertEquals($initialCount, ExtracurricularAttendance::count());
    }

    /**
     * Skenario E — Validasi Payload:
     * Request dengan status tidak valid atau struktur payload tidak lengkap ditolak HTTP 422.
     */
    public function test_store_attendance_payload_validation_rejects_invalid_data(): void
    {
        $ctx = $this->setupExtracurricularContext();

        Sanctum::actingAs($ctx['userCoach1']);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'invalid_status_value',
                ]
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendances.0.status']);
    }

    /**
     * Skenario F — Tanpa Autentikasi:
     * Permintaan tanpa token otentikasi Sanctum ditolak HTTP 401 Unauthorized.
     */
    public function test_store_attendance_unauthenticated_is_rejected(): void
    {
        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => 1,
            'attendances' => [],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Skenario G (Pilot 9 — A): Siswa non-anggota ditolak:
     * Pembina sah mengirim presensi untuk siswa yang ada di database tetapi bukan anggota kegiatan.
     * Harus ditolak HTTP 422 dan tidak ada record baru yang tersimpan.
     */
    public function test_store_attendance_for_non_member_student_is_rejected(): void
    {
        $ctx = $this->setupExtracurricularContext();

        // Siswa non-anggota: dibuat di kelas yang sama, tetapi TIDAK di-attach ke extracurricular1
        $nonMemberStudent = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Budi Non Anggota',
            'nis' => '1002',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440002',
            'status' => 'aktif',
        ]);

        Sanctum::actingAs($ctx['userCoach1']);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $nonMemberStudent->id,
                    'status' => 'hadir',
                ]
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);

        $this->assertDatabaseMissing('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $nonMemberStudent->id,
            'attendance_date' => '2026-10-12',
        ]);
    }

    /**
     * Skenario H (Pilot 9 — B): Batch campuran ditolak tanpa mutasi parsial:
     * Payload berisi Siswa A (anggota sah, record awal 'izin') dan Siswa B (non-anggota).
     * Seluruh request harus ditolak HTTP 422, record Siswa A tetap 'izin', dan Siswa B tidak memiliki record.
     */
    public function test_store_attendance_mixed_batch_rejects_all_and_prevents_partial_writes(): void
    {
        $ctx = $this->setupExtracurricularContext();

        // 1. Record awal untuk Siswa A (anggota sah) berstatus 'izin'
        $initialAttendance = ExtracurricularAttendance::create([
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $ctx['student']->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
            'attendance_date' => '2026-10-12',
            'status' => 'izin',
            'notes' => 'Izin sebelum penolakan batch',
        ]);

        $initialCount = ExtracurricularAttendance::count();

        // 2. Siswa B (non-anggota)
        $nonMemberStudent = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Citra Non Anggota',
            'nis' => '1003',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440003',
            'status' => 'aktif',
        ]);

        Sanctum::actingAs($ctx['userCoach1']);

        // 3. Kirim request batch: mencoba mengubah Siswa A menjadi 'hadir' dan mencatat Siswa B sebagai 'hadir'
        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'hadir',
                ],
                [
                    'student_id' => $nonMemberStudent->id,
                    'status' => 'hadir',
                ]
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);

        // 4. Verifikasi atomisitas: record Siswa A tetap utuh berstatus 'izin'
        $reloaded = $initialAttendance->fresh();
        $this->assertNotNull($reloaded);
        $this->assertEquals('izin', $reloaded->status);
        $this->assertEquals('Izin sebelum penolakan batch', $reloaded->notes);

        // 5. Verifikasi Siswa B tidak memiliki record sama sekali
        $this->assertDatabaseMissing('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $nonMemberStudent->id,
            'attendance_date' => '2026-10-12',
        ]);

        // 6. Pastikan jumlah total record tidak bertambah
        $this->assertEquals($initialCount, ExtracurricularAttendance::count());
    }

    /**
     * Skenario I (Pilot 9 — C): Semua siswa anggota sah tetap berhasil (Multi-member Happy Path):
     * Seluruh siswa dalam payload terdaftar sebagai anggota resmi ekstrakurikuler.
     * Request berhasil dengan HTTP 200 dan semua record presensi tersimpan benar.
     */
    public function test_store_attendance_for_all_valid_members_succeeds(): void
    {
        $ctx = $this->setupExtracurricularContext();

        // Siswa 2 sebagai anggota kedua yang sah
        $secondMember = Student::create([
            'school_class_id' => $ctx['schoolClass']->id,
            'name' => 'Dewi Anggota Sah',
            'nis' => '1004',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440004',
            'status' => 'aktif',
        ]);
        $ctx['extracurricular1']->students()->attach($secondMember->id);

        Sanctum::actingAs($ctx['userCoach1']);

        $response = $this->postJson('/api/teacher/extracurriculars/attendance', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'date' => '2026-10-12',
            'attendances' => [
                [
                    'student_id' => $ctx['student']->id,
                    'status' => 'hadir',
                ],
                [
                    'student_id' => $secondMember->id,
                    'status' => 'sakit',
                ]
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'hadir',
            'attendance_date' => '2026-10-12',
        ]);

        $this->assertDatabaseHas('extracurricular_attendances', [
            'extracurricular_id' => $ctx['extracurricular1']->id,
            'student_id' => $secondMember->id,
            'status' => 'sakit',
            'attendance_date' => '2026-10-12',
        ]);
    }
}
