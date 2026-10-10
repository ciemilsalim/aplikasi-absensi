<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_teacher_dashboard(): void
    {
        $response = $this->get(route('teacher.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_teacher_user_is_redirected_by_teacher_middleware(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get(route('teacher.dashboard'));
        $response->assertRedirect('/');
    }

    public function test_teacher_without_teacher_profile_is_forbidden(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        $response = $this->actingAs($user)->get(route('teacher.dashboard'));
        $response->assertStatus(403);
    }

    public function test_teacher_without_assigned_role_renders_no_role_view(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'name' => 'Guru Tanpa Peran',
            'nip' => '198501012010011001',
        ]);

        $response = $this->actingAs($user)->get(route('teacher.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('teacher.dashboard-no-role');
    }

    public function test_homeroom_teacher_cannot_update_attendance_for_other_class_student(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'name' => 'Wali Kelas A',
            'nip' => '198501012010011002',
        ]);

        $classA = SchoolClass::create(['name' => 'VII-A', 'teacher_id' => $teacher->id]);
        $classB = SchoolClass::create(['name' => 'VII-B']);

        $studentB = Student::create([
            'name' => 'Siswa B',
            'nis' => '10002',
            'school_class_id' => $classB->id,
        ]);

        $response = $this->actingAs($user)->post(route('teacher.mark.attendance'), [
            'student_id' => $studentB->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'tepat_waktu',
        ]);

        $response->assertSessionHas('error');
    }
}
