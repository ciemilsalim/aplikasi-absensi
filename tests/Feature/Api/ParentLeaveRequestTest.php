<?php

namespace Tests\Feature\Api;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParentLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Menyiapkan konteks pengujian: User Orang Tua, ParentModel, dan Siswa yang terhubung.
     */
    protected function setupParentWithStudent(): array
    {
        Carbon::setTestNow('2026-10-12 08:00:00');

        $user = User::factory()->create([
            'role' => 'parent',
        ]);

        $parent = ParentModel::create([
            'user_id' => $user->id,
            'name' => 'Bapak Budi',
            'phone_number' => '081234567890',
        ]);

        $student = Student::create([
            'name' => 'Budi Siswa',
            'nis' => '1001',
            'unique_id' => '550e8400-e29b-41d4-a716-446655440000',
            'status' => 'aktif',
        ]);

        $parent->students()->attach($student->id);

        return compact('user', 'parent', 'student');
    }

    /**
     * Skenario 1 — Pengajuan Izin Dengan Lampiran Berkas:
     * Orang tua mengunggah file bukti sakit/izin (attachment).
     * Endpoint diharapkan berhasil menyimpan file ke storage dan mencatat path-nya ke kolom database 'attachment'.
     */
    public function test_parent_can_submit_leave_request_with_attachment(): void
    {
        Storage::fake('public');
        $ctx = $this->setupParentWithStudent();

        Sanctum::actingAs($ctx['user']);

        $file = UploadedFile::fake()->image('surat_dokter.jpg', 600, 600);

        $response = $this->post('/api/parent/leave-requests', [
            'student_id' => $ctx['student']->id,
            'type' => 'sakit',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-16',
            'reason' => 'Sakit demam tinggi dan flu',
            'attachment' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Permohonan izin berhasil dikirim.',
            'data' => [
                'student_id' => $ctx['student']->id,
                'parent_id' => $ctx['parent']->id,
                'type' => 'sakit',
                'status' => 'pending',
            ],
        ]);

        $leaveRequestId = $response->json('data.id');
        $this->assertNotNull($leaveRequestId, 'ID permohonan izin harus dikembalikan pada response.');

        // Verifikasi bahwa kolom database adalah 'attachment' dan terisi path file
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequestId,
            'student_id' => $ctx['student']->id,
            'parent_id' => $ctx['parent']->id,
            'status' => 'pending',
        ]);

        $savedLeaveRequest = \App\Models\LeaveRequest::find($leaveRequestId);
        $this->assertNotNull($savedLeaveRequest->attachment, 'Kolom attachment di database harus terisi path file.');
        Storage::disk('public')->assertExists($savedLeaveRequest->attachment);
    }

    /**
     * Skenario 2 — Pengajuan Izin Tanpa Lampiran:
     * Memastikan alur pengajuan izin tanpa file lampiran tetap berfungsi dengan baik.
     */
    public function test_parent_can_submit_leave_request_without_attachment(): void
    {
        $ctx = $this->setupParentWithStudent();

        Sanctum::actingAs($ctx['user']);

        $response = $this->post('/api/parent/leave-requests', [
            'student_id' => $ctx['student']->id,
            'type' => 'izin',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-15',
            'reason' => 'Acara keluarga mendesak',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Permohonan izin berhasil dikirim.',
            'data' => [
                'student_id' => $ctx['student']->id,
                'parent_id' => $ctx['parent']->id,
                'type' => 'izin',
                'status' => 'pending',
            ],
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $ctx['student']->id,
            'parent_id' => $ctx['parent']->id,
            'type' => 'izin',
            'status' => 'pending',
            'attachment' => null,
        ]);
    }

    /**
     * Skenario 3 — Otorisasi Orang Tua Terhadap Siswa:
     * Orang tua tidak dapat mengajukan izin untuk siswa yang belum terhubung dengan akunnya.
     */
    public function test_parent_cannot_submit_leave_request_for_unlinked_student(): void
    {
        $ctx = $this->setupParentWithStudent();

        // Buat siswa lain yang tidak terhubung dengan orang tua
        $otherStudent = Student::create([
            'name' => 'Siswa Lain',
            'nis' => '2002',
            'unique_id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'aktif',
        ]);

        Sanctum::actingAs($ctx['user']);

        $response = $this->post('/api/parent/leave-requests', [
            'student_id' => $otherStudent->id,
            'type' => 'sakit',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-16',
            'reason' => 'Sakit demam',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Siswa tidak terhubung dengan akun Anda.',
        ]);
    }
}
