<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\ParentModel;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAttendance;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupLeaveContext(): array
    {
        // 2026-10-12 is Monday (weekday)
        Carbon::setTestNow('2026-10-12 08:00:00');

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
            'name' => 'Wali & Guru Mapel',
            'nip' => '198305052009011003',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $subject = Subject::create([
            'name' => 'Matematika',
            'code' => 'MTK',
        ]);

        $student = Student::create([
            'nis' => '1001',
            'nisn' => '0012345678',
            'name' => 'Ahmad Santoso',
            'school_class_id' => $schoolClass->id,
            'gender' => 'L',
            'status' => 'aktif',
        ]);

        if (Schema::hasTable('class_student')) {
            $schoolClass->students()->attach($student->id, ['semester_id' => $semester->id]);
        }

        $assignment = TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        // Monday schedule
        $schedule = Schedule::create([
            'teaching_assignment_id' => $assignment->id,
            'teacher_id' => $teacher->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
        ]);

        return compact('admin', 'teacher', 'schoolClass', 'subject', 'student', 'schedule', 'academicYear', 'semester');
    }

    public function test_admin_can_view_leave_requests_index(): void
    {
        $context = $this->setupLeaveContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.leave_requests.index'));

        $response->assertOk();
        $response->assertViewIs('admin.leave_requests.index');
        $response->assertViewHasAll([
            'pendingRequests',
            'manualInterventions',
            'processedRequests',
            'classes',
        ]);
    }

    public function test_admin_can_get_students_by_class(): void
    {
        $context = $this->setupLeaveContext();

        $response = $this->actingAs($context['admin'])
            ->getJson(route('admin.leave_requests.students_by_class', [
                'class_id' => $context['schoolClass']->id,
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'students' => [
                '*' => ['id', 'name', 'nis', 'photo_url']
            ]
        ]);
        $response->assertJsonFragment(['name' => 'Ahmad Santoso']);
    }

    public function test_admin_can_store_manual_leave_intervention_and_sync_attendances(): void
    {
        $context = $this->setupLeaveContext();
        $student = $context['student'];

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.leave_requests.store_manual'), [
                'student_ids' => [$student->id],
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-12',
                'type' => 'sakit',
                'submission_source' => 'whatsapp',
                'reason' => 'Demam tinggi dan istirahat dokter',
            ]);

        $response->assertRedirect(route('admin.leave_requests.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $student->id,
            'type' => 'sakit',
            'status' => 'approved',
            'submission_source' => 'whatsapp',
        ]);

        // Verify Daily attendance synced
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'sakit',
        ]);

        // Verify Subject attendance synced
        $this->assertDatabaseHas('subject_attendances', [
            'student_id' => $student->id,
            'status' => 'sakit',
        ]);
    }

    public function test_admin_can_edit_and_update_manual_intervention(): void
    {
        $context = $this->setupLeaveContext();
        $student = $context['student'];

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'type' => 'izin',
            'submission_source' => 'surat',
            'reason' => 'Keperluan keluarga',
            'status' => 'approved',
            'created_by' => $context['admin']->id,
            'approved_by' => $context['admin']->id,
        ]);

        // Test Edit json
        $response = $this->actingAs($context['admin'])
            ->getJson(route('admin.leave_requests.edit', $leave->id));

        $response->assertOk();
        $response->assertJsonFragment(['type' => 'izin', 'reason' => 'Keperluan keluarga']);

        // Test Update
        $updateResponse = $this->actingAs($context['admin'])
            ->put(route('admin.leave_requests.update', $leave->id), [
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-12',
                'type' => 'sakit',
                'submission_source' => 'telepon',
                'reason' => 'Konfirmasi sakit dokter',
            ]);

        $updateResponse->assertRedirect(route('admin.leave_requests.index'));
        $leave->refresh();
        $this->assertEquals('sakit', $leave->type);
        $this->assertEquals('telepon', $leave->submission_source);
    }

    public function test_admin_can_approve_parent_leave_request(): void
    {
        $context = $this->setupLeaveContext();
        $student = $context['student'];

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Orang Tua Siswa',
        ]);

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'type' => 'izin',
            'submission_source' => 'aplikasi_ortu',
            'reason' => 'Izin menjenguk nenek',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.leave_requests.approve', $leave->id));

        $response->assertRedirect(route('admin.leave_requests.index'));
        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($context['admin']->id, $leave->approved_by);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status' => 'izin',
        ]);
    }

    public function test_admin_can_reject_parent_leave_request(): void
    {
        $context = $this->setupLeaveContext();
        $student = $context['student'];

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Orang Tua Siswa',
        ]);

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'type' => 'izin',
            'submission_source' => 'aplikasi_ortu',
            'reason' => 'Tanpa keterangan jelas',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.leave_requests.reject', $leave->id), [
                'rejection_reason' => 'Alasan izin tidak memenuhi kriteria dispensasi sekolah.',
            ]);

        $response->assertRedirect(route('admin.leave_requests.index'));
        $leave->refresh();
        $this->assertEquals('rejected', $leave->status);
        $this->assertEquals('Alasan izin tidak memenuhi kriteria dispensasi sekolah.', $leave->rejection_reason);
    }

    public function test_admin_can_destroy_intervention_and_neutralize_attendance(): void
    {
        $context = $this->setupLeaveContext();
        $student = $context['student'];

        $leave = LeaveRequest::create([
            'student_id' => $student->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'type' => 'sakit',
            'submission_source' => 'whatsapp',
            'reason' => 'Sakit flu',
            'status' => 'approved',
            'created_by' => $context['admin']->id,
            'approved_by' => $context['admin']->id,
        ]);

        Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => Carbon::parse('2026-10-12 00:00:00'),
            'status' => 'sakit',
        ]);

        $response = $this->actingAs($context['admin'])
            ->delete(route('admin.leave_requests.destroy', $leave->id));

        $response->assertRedirect(route('admin.leave_requests.index'));
        $this->assertDatabaseMissing('leave_requests', ['id' => $leave->id]);
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $student->id,
            'status' => 'sakit',
        ]);
    }
}
