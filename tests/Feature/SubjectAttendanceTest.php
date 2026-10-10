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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupTeacherWithStudentAndSchedule(): array
    {
        // Tetapkan waktu pengujian pada hari kerja (Senin, pk 08:00)
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
            'nis' => '1001',
            'unique_id' => 'STU-1001',
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

    public function test_guest_is_redirected_to_login_when_accessing_scanner(): void
    {
        $response = $this->get('/teacher/subject-attendance/scanner/1');
        $response->assertRedirect(route('login'));
    }

    public function test_teacher_can_view_scanner_for_authorized_schedule(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        $response = $this->actingAs($ctx['user'])->get(route('teacher.subject.attendance.scanner', $ctx['schedule']));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.subject_attendance_scanner');
        $response->assertViewHas('totalClassStudents', 1);
    }

    public function test_teacher_is_forbidden_to_view_scanner_for_unauthorized_schedule(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        // Buat guru lain
        $otherUser = User::factory()->create(['role' => 'teacher']);
        Teacher::create([
            'user_id' => $otherUser->id,
            'name' => 'Guru Lain',
            'nip' => '199001012015011003',
        ]);

        $response = $this->actingAs($otherUser)->get(route('teacher.subject.attendance.scanner', $ctx['schedule']));
        $response->assertRedirect(route('teacher.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_teacher_can_record_manual_attendance_successfully(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        $response = $this->actingAs($ctx['user'])->postJson(route('teacher.subject.attendance.mark_manual'), [
            'student_id' => $ctx['student']->id,
            'schedule_id' => $ctx['schedule']->id,
            'status' => 'hadir',
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

        $this->assertDatabaseHas('subject_attendances', [
            'schedule_id' => $ctx['schedule']->id,
            'student_id' => $ctx['student']->id,
            'status' => 'hadir',
        ]);
    }

    public function test_teacher_cannot_record_manual_attendance_for_unauthorized_schedule(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        $otherUser = User::factory()->create(['role' => 'teacher']);
        Teacher::create([
            'user_id' => $otherUser->id,
            'name' => 'Guru Lain',
            'nip' => '199001012015011003',
        ]);

        $response = $this->actingAs($otherUser)->postJson(route('teacher.subject.attendance.mark_manual'), [
            'student_id' => $ctx['student']->id,
            'schedule_id' => $ctx['schedule']->id,
            'status' => 'hadir',
            'date' => '2026-10-12',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    public function test_teacher_can_view_attendance_history(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        // Buat record attendance
        SubjectAttendance::create([
            'schedule_id' => $ctx['schedule']->id,
            'student_id' => $ctx['student']->id,
            'teacher_id' => $ctx['teacher']->id,
            'status' => 'hadir',
            'created_at' => '2026-10-12 08:00:00',
        ]);

        $response = $this->actingAs($ctx['user'])->get(route('teacher.subject.attendance.history', ['date' => '2026-10-12']));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.subject_attendance_history');
        $response->assertViewHas('attendances');
    }

    public function test_teacher_can_view_report_form_and_preview(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        // Form
        $formResponse = $this->actingAs($ctx['user'])->get(route('teacher.subject.attendance.report'));
        $formResponse->assertStatus(200);
        $formResponse->assertViewIs('teacher.report_form');

        // Preview
        $previewResponse = $this->actingAs($ctx['user'])->get(route('teacher.subject.attendance.preview', [
            'activity_type' => 'regular',
            'school_class_id' => $ctx['schoolClass']->id,
            'subject_id' => $ctx['subject']->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $previewResponse->assertStatus(200);
        $previewResponse->assertViewIs('teacher.report_preview');
    }

    public function test_teacher_can_view_charts_page_and_query_chart_data(): void
    {
        $ctx = $this->setupTeacherWithStudentAndSchedule();

        $chartsResponse = $this->actingAs($ctx['user'])->get(route('teacher.subject.attendance.charts'));
        $chartsResponse->assertStatus(200);
        $chartsResponse->assertViewIs('teacher.subject_charts');

        $dataResponse = $this->actingAs($ctx['user'])->postJson(route('teacher.subject.attendance.charts.data'), [
            'activity_type' => 'regular',
            'school_class_id' => $ctx['schoolClass']->id,
            'subject_id' => $ctx['subject']->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'period' => 'weekly',
        ]);

        $dataResponse->assertStatus(200);
        $dataResponse->assertJsonStructure([
            'success',
            'summary',
            'metrics',
            'trendLabels',
            'trendData'
        ]);
    }
}
