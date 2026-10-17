<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherLeaveRequestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 08:00:00'); // Senin
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Menyiapkan data acuan untuk pengujian otorisasi:
     * - Tahun ajaran & semester aktif
     * - Guru A sebagai wali kelas VIII-A beserta Siswa A
     * - Guru B sebagai wali kelas VIII-B beserta Siswa B
     * - Guru tanpa penugasan wali kelas
     * - Pengguna biasa tanpa profil guru
     */
    protected function setupTestContext(): array
    {
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

        // Guru A & Kelas A
        $userA = User::factory()->create(['role' => 'teacher']);
        $teacherA = Teacher::create([
            'user_id' => $userA->id,
            'name' => 'Wali Kelas A',
            'nip' => '198001012005011001',
        ]);
        $classA = SchoolClass::create([
            'name' => 'VIII-A',
            'teacher_id' => $teacherA->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);
        $studentA = Student::create([
            'name' => 'Siswa Kelas A',
            'nis' => '10001',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440001',
            'school_class_id' => $classA->id,
            'status' => 'aktif',
        ]);

        // Guru B & Kelas B
        $userB = User::factory()->create(['role' => 'teacher']);
        $teacherB = Teacher::create([
            'user_id' => $userB->id,
            'name' => 'Wali Kelas B',
            'nip' => '198001012005011002',
        ]);
        $classB = SchoolClass::create([
            'name' => 'VIII-B',
            'teacher_id' => $teacherB->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);
        $studentB = Student::create([
            'name' => 'Siswa Kelas B',
            'nis' => '10002',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440002',
            'school_class_id' => $classB->id,
            'status' => 'aktif',
        ]);

        // Guru tanpa penugasan wali kelas
        $userNoClass = User::factory()->create(['role' => 'teacher']);
        $teacherNoClass = Teacher::create([
            'user_id' => $userNoClass->id,
            'name' => 'Guru Tanpa Kelas',
            'nip' => '198001012005011003',
        ]);

        // User tanpa profil guru (misal role student)
        $userNonTeacher = User::factory()->create(['role' => 'student']);

        return compact(
            'academicYear', 'semester',
            'userA', 'teacherA', 'classA', 'studentA',
            'userB', 'teacherB', 'classB', 'studentB',
            'userNoClass', 'teacherNoClass',
            'userNonTeacher'
        );
    }

    /**
     * Skenario A: Pengguna tanpa autentikasi tidak dapat memproses izin siswa (HTTP 401).
     */
    public function test_unauthenticated_user_cannot_update_leave_request_status(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequest = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'sakit',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-14',
            'reason' => 'Demam',
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario B: Pengguna terautentikasi tanpa profil guru ditolak (HTTP 403).
     */
    public function test_authenticated_user_without_teacher_profile_is_forbidden(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequest = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'sakit',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-14',
            'reason' => 'Demam',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userNonTeacher']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Anda bukan Wali Kelas dari kelas manapun.',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario C: Guru yang bukan wali kelas tidak boleh memproses izin (HTTP 403).
     */
    public function test_teacher_without_homeroom_class_is_forbidden(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequest = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'izin',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-13',
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userNoClass']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Anda bukan Wali Kelas dari kelas manapun.',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario D: Guru dari kelas lain tidak boleh menyetujui izin siswa kelas lain (Cross-Class Approval IDOR -> HTTP 403).
     */
    public function test_homeroom_teacher_of_other_class_cannot_approve_leave_request(): void
    {
        $ctx = $this->setupTestContext();

        // Izin diajukan oleh Siswa Kelas B
        $leaveRequestB = LeaveRequest::create([
            'student_id' => $ctx['studentB']->id,
            'type' => 'sakit',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-14',
            'reason' => 'Sakit perut',
            'status' => 'pending',
        ]);

        // Guru A (wali kelas A) mencoba menyetujui izin Siswa B
        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestB->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Akses ditolak. Anda bukan Wali Kelas dari siswa ini.',
        ]);

        // Pastikan status permohonan tetap pending dan tidak termutasi
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestB->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);

        // Pastikan data attendances sama sekali tidak bertambah
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario E: Guru dari kelas lain tidak boleh menolak izin siswa kelas lain (Cross-Class Rejection IDOR -> HTTP 403).
     */
    public function test_homeroom_teacher_of_other_class_cannot_reject_leave_request(): void
    {
        $ctx = $this->setupTestContext();

        // Izin diajukan oleh Siswa Kelas B
        $leaveRequestB = LeaveRequest::create([
            'student_id' => $ctx['studentB']->id,
            'type' => 'izin',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-13',
            'reason' => 'Acara keluarga',
            'status' => 'pending',
        ]);

        // Guru A (wali kelas A) mencoba menolak izin Siswa B
        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestB->id}/status", [
            'status' => 'rejected',
            'rejection_reason' => 'Alasan ditolak oleh guru kelas lain',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Akses ditolak. Anda bukan Wali Kelas dari siswa ini.',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestB->id,
            'status' => 'pending',
            'rejection_reason' => null,
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario F1: Wali kelas yang sah dapat menyetujui izin siswa di kelasnya dan sinkronisasi Attendance berhasil.
     */
    public function test_authorized_homeroom_teacher_can_approve_leave_request_and_sync_attendance(): void
    {
        $ctx = $this->setupTestContext();

        // Izin 2 hari: Selasa 2026-10-13 s.d. Rabu 2026-10-14
        $leaveRequestA = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'sakit',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-14',
            'reason' => 'Sakit flu',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestA->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Permohonan berhasil disetujui.',
            'data' => [
                'id' => $leaveRequestA->id,
                'status' => 'approved',
                'approved_by' => $ctx['userA']->id,
            ],
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestA->id,
            'status' => 'approved',
            'approved_by' => $ctx['userA']->id,
        ]);

        // Verifikasi pembuatan 2 record absensi pada tabel attendances
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $ctx['studentA']->id,
            'status' => 'sakit',
            'attendance_time' => '2026-10-13 07:00:00',
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $ctx['studentA']->id,
            'status' => 'sakit',
            'attendance_time' => '2026-10-14 07:00:00',
        ]);
    }

    /**
     * Skenario F2: Wali kelas yang sah dapat menolak izin siswa di kelasnya dengan alasan penolakan.
     */
    public function test_authorized_homeroom_teacher_can_reject_leave_request_with_reason(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequestA = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'izin',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-13',
            'reason' => 'Liburan pribadi',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestA->id}/status", [
            'status' => 'rejected',
            'rejection_reason' => 'Izin tidak dapat diberikan untuk liburan pribadi pada hari efektif belajar.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Permohonan berhasil ditolak.',
            'data' => [
                'id' => $leaveRequestA->id,
                'status' => 'rejected',
                'rejection_reason' => 'Izin tidak dapat diberikan untuk liburan pribadi pada hari efektif belajar.',
                'approved_by' => $ctx['userA']->id,
            ],
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestA->id,
            'status' => 'rejected',
            'rejection_reason' => 'Izin tidak dapat diberikan untuk liburan pribadi pada hari efektif belajar.',
            'approved_by' => $ctx['userA']->id,
        ]);

        // Tidak ada record attendances yang dibuat ketika izin ditolak
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario G1: Permohonan izin yang sudah diproses sebelumnya tidak dapat diproses ulang (HTTP 422).
     */
    public function test_existing_rule_cannot_process_already_processed_leave_request(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequestA = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'sakit',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-14',
            'reason' => 'Demam',
            'status' => 'approved',
            'approved_by' => $ctx['userA']->id,
        ]);

        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestA->id}/status", [
            'status' => 'rejected',
            'rejection_reason' => 'Mencoba membatalkan',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Permohonan ini sudah diproses sebelumnya.',
        ]);
    }

    /**
     * Skenario G2: Permohonan izin lebih dari 3 hari ditolak (HTTP 403).
     */
    public function test_existing_rule_leave_request_over_three_days_is_forbidden(): void
    {
        $ctx = $this->setupTestContext();

        // 4 hari: 2026-10-13 s.d. 2026-10-16
        $leaveRequestA = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'izin',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-16',
            'reason' => 'Izin panjang luar kota',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestA->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Izin lebih dari 3 hari harus diproses langsung melalui Kepala Sekolah.',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestA->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    /**
     * Skenario G3: Penolakan tanpa alasan ditolak oleh validasi (HTTP 422).
     */
    public function test_existing_rule_rejection_requires_rejection_reason(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequestA = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'izin',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-13',
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestA->id}/status", [
            'status' => 'rejected',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rejection_reason']);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestA->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    /**
     * Skenario G4: Status selain approved atau rejected ditolak oleh validasi (HTTP 422).
     */
    public function test_existing_rule_invalid_status_is_rejected(): void
    {
        $ctx = $this->setupTestContext();

        $leaveRequestA = LeaveRequest::create([
            'student_id' => $ctx['studentA']->id,
            'type' => 'izin',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-13',
            'reason' => 'Keperluan keluarga',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($ctx['userA']);

        $response = $this->patchJson("/api/teacher/leave-requests/{$leaveRequestA->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestA->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }
}
