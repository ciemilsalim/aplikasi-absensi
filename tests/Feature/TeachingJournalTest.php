<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\TeachingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setupTeacherWithAssignment()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru IPA',
            'nip' => '198203152008011005',
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

        $schoolClass = SchoolClass::create(['name' => 'VIII-A']);
        $subject = Subject::create(['name' => 'Ilmu Pengetahuan Alam']);

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
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '08:50:00',
            'schedule_type' => 'regular',
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        return compact('user', 'teacher', 'schoolClass', 'subject', 'assignment', 'schedule', 'academicYear', 'semester');
    }

    public function test_guest_is_redirected_to_login_when_accessing_journals(): void
    {
        $response = $this->get(route('teacher.journals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_teacher_can_view_journal_index(): void
    {
        $ctx = $this->setupTeacherWithAssignment();

        $response = $this->actingAs($ctx['user'])->get(route('teacher.journals.index'));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.journals.index');
    }

    public function test_teacher_can_view_journal_create_page(): void
    {
        $ctx = $this->setupTeacherWithAssignment();

        $response = $this->actingAs($ctx['user'])->get(route('teacher.journals.create'));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.journals.create');
    }

    public function test_teacher_can_store_journal_successfully(): void
    {
        $ctx = $this->setupTeacherWithAssignment();

        $response = $this->actingAs($ctx['user'])->post(route('teacher.journals.store'), [
            'schedule_id' => $ctx['schedule']->id,
            'date' => now()->format('Y-m-d'),
            'jp' => 2,
            'learning_objective' => 'Siswa dapat memahami sistem pernapasan manusia',
            'topic' => 'Sistem Pernapasan',
            'activity' => 'Eksperimen model paru-paru sederhana',
            'assessment' => 'Formatif - Observasi Lembar Kerja',
            'reflection' => 'Mayoritas siswa aktif dan memahami materi',
            'follow_up' => 'Penguatan materi pada pertemuan selanjutnya',
            'students_achieved_count' => 28,
            'students_remedial_count' => 2,
            'students_enrichment_count' => 0,
        ]);

        $response->assertRedirect(route('teacher.journals.index'));
        $this->assertDatabaseHas('teaching_journals', [
            'teacher_id' => $ctx['teacher']->id,
            'topic' => 'Sistem Pernapasan',
            'jp' => 2,
        ]);
    }

    public function test_teacher_cannot_store_journal_for_unauthorized_schedule(): void
    {
        $ctx = $this->setupTeacherWithAssignment();

        // Buat jadwal untuk guru lain
        $otherUser = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = Teacher::create([
            'user_id' => $otherUser->id,
            'name' => 'Guru Lain',
            'nip' => '198901012015011009',
        ]);
        $otherSubject = Subject::create(['name' => 'Matematika']);
        $otherAssignment = TeachingAssignment::create([
            'teacher_id' => $otherTeacher->id,
            'school_class_id' => $ctx['schoolClass']->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $ctx['academicYear']->id,
            'semester_id' => $ctx['semester']->id,
        ]);
        $otherSchedule = Schedule::create([
            'teaching_assignment_id' => $otherAssignment->id,
            'teacher_id' => $otherTeacher->id,
            'school_class_id' => $ctx['schoolClass']->id,
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '10:20:00',
            'schedule_type' => 'regular',
        ]);

        $response = $this->actingAs($ctx['user'])->post(route('teacher.journals.store'), [
            'schedule_id' => $otherSchedule->id,
            'date' => now()->format('Y-m-d'),
            'jp' => 2,
            'learning_objective' => 'Materi tidak sah',
            'topic' => 'Topik',
            'activity' => 'Aktivitas',
            'assessment' => 'Formatif',
            'reflection' => 'Refleksi',
            'follow_up' => 'Tindak lanjut',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('teaching_journals', [
            'learning_objective' => 'Materi tidak sah',
        ]);
    }

    public function test_teacher_can_access_reports_and_reflection(): void
    {
        $ctx = $this->setupTeacherWithAssignment();

        $responseWeekly = $this->actingAs($ctx['user'])->get(route('teacher.journals.weekly'));
        $responseWeekly->assertStatus(200);

        $responseSemester = $this->actingAs($ctx['user'])->get(route('teacher.journals.semester'));
        $responseSemester->assertStatus(200);

        $responseReflection = $this->actingAs($ctx['user'])->get(route('teacher.journals.reflection'));
        $responseReflection->assertStatus(200);
    }
}
