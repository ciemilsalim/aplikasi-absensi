<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Calendar;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckAbsentStudentsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupCommandContext(?string $testNow = '2026-10-12 08:35:00'): array
    {
        if ($testNow) {
            Carbon::setTestNow($testNow);
        }

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
            'name' => 'Wali Kelas VII-A',
            'nip' => '198401012010011005',
        ]);

        $schoolClass = SchoolClass::create([
            'name' => 'VII-A',
            'teacher_id' => $teacher->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        // Siswa 1: Aktif, belum absen -> harus jadi alpa
        $studentAbsent = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '2001',
            'name' => 'Siswa Alpa',
            'gender' => 'L',
            'unique_id' => (string) Str::uuid(),
            'status' => 'aktif',
        ]);

        // Siswa 2: Aktif, sudah masuk -> tidak diubah
        $studentPresent = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '2002',
            'name' => 'Siswa Masuk',
            'gender' => 'P',
            'unique_id' => (string) Str::uuid(),
            'status' => 'aktif',
        ]);
        Attendance::create([
            'student_id' => $studentPresent->id,
            'attendance_time' => Carbon::parse('2026-10-12 07:15:00'),
            'status' => 'tepat_waktu',
        ]);

        // Siswa 3: Inaktif (lulus) -> tidak boleh ditandai alpa
        $studentAlumni = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '2003',
            'name' => 'Siswa Alumni',
            'gender' => 'L',
            'unique_id' => (string) Str::uuid(),
            'status' => 'lulus',
        ]);

        // Siswa 4: Aktif, ada pengajuan izin disetujui -> harus jadi izin
        $studentLeave = Student::create([
            'school_class_id' => $schoolClass->id,
            'nis' => '2004',
            'name' => 'Siswa Izin',
            'gender' => 'P',
            'unique_id' => (string) Str::uuid(),
            'status' => 'aktif',
        ]);
        LeaveRequest::create([
            'student_id' => $studentLeave->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'type' => 'izin',
            'status' => 'approved',
            'reason' => 'Keperluan keluarga',
        ]);

        // Hubungkan orang tua ke studentAbsent
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parent = ParentModel::create([
            'user_id' => $parentUser->id,
            'name' => 'Orang Tua Siswa Alpa',
            'phone' => '081234567890',
        ]);
        $studentAbsent->parents()->attach($parent->id);

        return compact('studentAbsent', 'studentPresent', 'studentAlumni', 'studentLeave', 'parentUser');
    }

    public function test_command_marks_active_unrecorded_students_as_alpa_and_excuses_approved_leave(): void
    {
        $context = $this->setupCommandContext();
        Setting::updateOrCreate(['key' => 'send_absent_notification'], ['value' => 'on']);

        $this->artisan('attendance:check-absent')
            ->expectsOutputToContain('1 siswa dicatat alpa, 1 siswa dicatat dari izin/sakit disetujui.')
            ->assertExitCode(0);

        // Siswa alpa tercatat
        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentAbsent']->id,
            'status' => 'alpa',
        ]);

        // Siswa izin tercatat dari leave request
        $this->assertDatabaseHas('attendances', [
            'student_id' => $context['studentLeave']->id,
            'status' => 'izin',
        ]);

        // Siswa alumni tidak tercatat
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $context['studentAlumni']->id,
        ]);

        // Notifikasi dibuat untuk orang tua
        $this->assertDatabaseHas('notifications', [
            'user_id' => $context['parentUser']->id,
            'title' => 'Informasi Kehadiran Ananda Siswa Alpa',
        ]);
    }

    public function test_command_skips_on_weekends(): void
    {
        $this->setupCommandContext('2026-10-11 08:30:00'); // Minggu

        $this->artisan('attendance:check-absent')
            ->expectsOutputToContain('Hari ini akhir pekan (Sabtu/Minggu). Pengecekan alpa dilewati.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('attendances', [
            'status' => 'alpa',
        ]);
    }

    public function test_command_skips_on_holidays(): void
    {
        Carbon::setTestNow('2026-10-12 08:30:00'); // Senin
        $this->setupCommandContext();

        Calendar::create([
            'title' => 'Hari Libur Nasional',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'is_holiday' => true,
        ]);

        $this->artisan('attendance:check-absent')
            ->expectsOutputToContain('Hari ini terdaftar sebagai: Hari Libur Nasional. Pengecekan alpa dilewati.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('attendances', [
            'status' => 'alpa',
        ]);
    }
}
