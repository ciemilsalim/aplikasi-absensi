<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherAttendanceQrTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Menyiapkan konteks pengujian: Guru Wali Kelas, Kelas Binaan, Tahun Ajaran Aktif, dan Siswa.
     */
    protected function setupTeacherContext(string $uuid = '550e8400-e29b-41d4-a716-446655440000', string $nis = '1001'): array
    {
        // Set waktu pengujian ke hari Senin pukul 07:15:00 WIB (hari kerja sebelum batas masuk 07:30)
        Carbon::setTestNow('2026-10-12 07:15:00');

        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'name' => 'Wali Kelas 7A',
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
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $student = Student::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Budi Siswa',
            'nis' => $nis,
            'unique_id' => $uuid,
            'status' => 'aktif',
        ]);

        return compact('user', 'teacher', 'schoolClass', 'student', 'academicYear', 'semester');
    }

    protected function validCoordinates(): array
    {
        $lat = (float) (Setting::where('key', 'school_latitude')->value('value') ?? -0.897424);
        $lng = (float) (Setting::where('key', 'school_longitude')->value('value') ?? 119.873335);

        return [
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    /**
     * Skenario 1 — UUID Murni:
     * Kartu fisik siswa mencetak UUID murni 36 karakter (misal: 550e8400-e29b-41d4-a716-446655440000).
     * Endpoint API /api/teacher/attendance/scan diharapkan berhasil mengenali siswa dan mencatat presensi datang.
     */
    public function test_scan_attendance_with_pure_uuid_qr_code_succeeds(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $ctx = $this->setupTeacherContext(uuid: $uuid, nis: '1001');

        Sanctum::actingAs($ctx['user']);

        $coords = $this->validCoordinates();
        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => $uuid,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Absensi datang berhasil dicatat',
            'attendance_status' => 'tepat_waktu',
            'student_name' => $ctx['student']->name,
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $ctx['student']->id,
            'status' => 'tepat_waktu',
        ]);
    }

    /**
     * Skenario 2 — Format Gabungan NIS-UUID:
     * Memindai format gabungan {nis}-{uuid} yang didukung kontrak.
     */
    public function test_scan_attendance_with_combined_nis_and_uuid_qr_code_succeeds(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $nis = '1001';
        $ctx = $this->setupTeacherContext(uuid: $uuid, nis: $nis);

        Sanctum::actingAs($ctx['user']);

        $combinedQr = "{$nis}-{$uuid}";
        $coords = $this->validCoordinates();

        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => $combinedQr,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Absensi datang berhasil dicatat',
            'student_name' => $ctx['student']->name,
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $ctx['student']->id,
            'status' => 'tepat_waktu',
        ]);
    }

    /**
     * Skenario 3 — Input NIS Manual Tunggal:
     * Memindai atau memasukkan NIS manual tanpa UUID (misal: 1001).
     */
    public function test_scan_attendance_with_plain_nis_succeeds(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $nis = '1001';
        $ctx = $this->setupTeacherContext(uuid: $uuid, nis: $nis);

        Sanctum::actingAs($ctx['user']);

        $coords = $this->validCoordinates();

        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => $nis,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Absensi datang berhasil dicatat',
            'attendance_status' => 'tepat_waktu',
            'student_name' => $ctx['student']->name,
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $ctx['student']->id,
            'status' => 'tepat_waktu',
        ]);
    }

    /**
     * Skenario 4 — Input Tidak Valid:
     * Memindai kode yang tidak cocok dengan siswa mana pun harus menghasilkan 404 Siswa tidak ditemukan.
     */
    public function test_scan_attendance_with_invalid_qr_code_returns_404(): void
    {
        $ctx = $this->setupTeacherContext();

        Sanctum::actingAs($ctx['user']);

        $coords = $this->validCoordinates();
        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => 'kode-acak-tidak-ditemukan',
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Siswa tidak ditemukan.',
        ]);
    }

    /**
     * Skenario 5 — Otorisasi Kelas:
     * Guru wali kelas tidak dapat mencatat presensi siswa di luar kelas binaannya.
     */
    public function test_scan_attendance_for_student_outside_homeroom_class_is_forbidden(): void
    {
        $ctx = $this->setupTeacherContext(uuid: '550e8400-e29b-41d4-a716-446655440000', nis: '1001');

        // Buat kelas lain dan siswa di kelas lain tersebut
        $otherClass = SchoolClass::create([
            'name' => 'VII-B',
            'teacher_id' => null,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
        ]);

        $otherStudentUuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $otherStudentNis = '2002';
        $otherStudent = Student::create([
            'school_class_id' => $otherClass->id,
            'name' => 'Siswa Kelas Lain',
            'nis' => $otherStudentNis,
            'unique_id' => $otherStudentUuid,
            'status' => 'aktif',
        ]);

        Sanctum::actingAs($ctx['user']);

        $coords = $this->validCoordinates();
        // Gunakan format gabungan NIS-UUID agar parser menemukan siswa di DB,
        // lalu verifikasi penolakan otorisasi kelas (HTTP 403)
        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => "{$otherStudentNis}-{$otherStudentUuid}",
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Siswa ini bukan bagian dari kelas Anda.',
        ]);
    }

    /**
     * Skenario 6 — Otorisasi Profil Guru:
     * Pengguna terautentikasi tanpa profil guru tidak boleh menyebabkan 500 error,
     * melainkan ditolak dengan HTTP 403 'Anda bukan Wali Kelas.'.
     */
    public function test_scan_attendance_by_user_without_teacher_profile_is_forbidden(): void
    {
        $userWithoutTeacher = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($userWithoutTeacher);

        $coords = $this->validCoordinates();
        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => '550e8400-e29b-41d4-a716-446655440000',
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Anda bukan Wali Kelas.',
        ]);
    }

    /**
     * Skenario 7 — Otorisasi Guru Non-Wali Kelas:
     * Guru yang tidak memiliki kelas binaan harus ditolak dengan HTTP 403.
     */
    public function test_scan_attendance_by_teacher_without_homeroom_class_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru Biasa Non Wali',
            'nip' => '199001012015011003',
        ]);

        Sanctum::actingAs($user);

        $coords = $this->validCoordinates();
        $response = $this->postJson('/api/teacher/attendance/scan', [
            'student_unique_id' => '550e8400-e29b-41d4-a716-446655440000',
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'type' => 'in',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Anda bukan Wali Kelas.',
        ]);
    }
}
