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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeachingJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupSupervisionContext(): array
    {
        Carbon::setTestNow('2026-10-12 09:00:00');

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
            'name' => 'Guru Pengajar',
            'nip' => '198501012010011002',
        ]);

        $subject = Subject::create([
            'name' => 'Matematika',
            'code' => 'MTK',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
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
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
        ]);

        $journal1 = TeachingJournal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'date' => '2026-10-10',
            'jp' => 2,
            'learning_objective' => 'Memahami Aljabar',
            'topic' => 'Aljabar Dasar',
            'activity' => 'Diskusi kelompok',
            'assessment' => 'Formatif',
            'reflection' => 'Berjalan lancar',
            'follow_up' => 'Latihan mandiri',
            'material_content' => 'Aljabar Dasar - Memahami Aljabar',
            'is_verified' => false,
        ]);

        $journal2 = TeachingJournal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'school_class_id' => $schoolClass->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'date' => '2026-10-11',
            'jp' => 2,
            'learning_objective' => 'Operasi Aljabar',
            'topic' => 'Penjumlahan Aljabar',
            'activity' => 'Latihan soal',
            'assessment' => 'Formatif',
            'reflection' => 'Sebagian siswa butuh bimbingan',
            'follow_up' => 'Remedial terarah',
            'material_content' => 'Penjumlahan Aljabar - Operasi Aljabar',
            'is_verified' => false,
        ]);

        return compact('admin', 'teacher', 'subject', 'schoolClass', 'journal1', 'journal2');
    }

    public function test_admin_can_view_supervision_index(): void
    {
        $context = $this->setupSupervisionContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.teaching_journals.index'));

        $response->assertOk();
        $response->assertViewIs('admin.teaching_journals.index');
        $response->assertViewHasAll([
            'journals',
            'teachers',
            'subjects',
            'classes',
            'totalJournals',
            'verifiedJournals',
            'unverifiedJournals',
            'activeTeachersCount',
        ]);
    }

    public function test_admin_can_view_teacher_supervision_detail(): void
    {
        $context = $this->setupSupervisionContext();

        $response = $this->actingAs($context['admin'])
            ->get(route('admin.teaching_journals.show', $context['teacher']->id));

        $response->assertOk();
        $response->assertViewIs('admin.teaching_journals.show');
        $response->assertViewHasAll([
            'teacher',
            'journals',
            'subjects',
            'classes',
            'totalJp',
            'totalVerified',
        ]);
    }

    public function test_admin_can_verify_individual_journal(): void
    {
        $context = $this->setupSupervisionContext();
        $journal = $context['journal1'];

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.teaching_journals.verify', $journal->id), [
                'is_verified' => 1,
                'supervisor_notes' => 'Bagus dan terstruktur.',
            ]);

        $response->assertSessionHas('success');
        $journal->refresh();
        $this->assertTrue((bool) $journal->is_verified);
        $this->assertEquals('Bagus dan terstruktur.', $journal->supervisor_notes);
        $this->assertEquals($context['admin']->id, $journal->verified_by);
    }

    public function test_admin_can_batch_verify_journals(): void
    {
        $context = $this->setupSupervisionContext();
        $journal1 = $context['journal1'];
        $journal2 = $context['journal2'];

        $response = $this->actingAs($context['admin'])
            ->post(route('admin.teaching_journals.batch_verify'), [
                'journal_ids' => [$journal1->id, $journal2->id],
                'supervisor_notes' => 'Batch verifikasi kurikulum.',
            ]);

        $response->assertSessionHas('success');
        $journal1->refresh();
        $journal2->refresh();

        $this->assertTrue((bool) $journal1->is_verified);
        $this->assertTrue((bool) $journal2->is_verified);
        $this->assertEquals('Batch verifikasi kurikulum.', $journal1->supervisor_notes);
        $this->assertEquals('Batch verifikasi kurikulum.', $journal2->supervisor_notes);
    }
}
