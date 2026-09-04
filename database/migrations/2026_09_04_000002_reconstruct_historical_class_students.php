<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\Schedule;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Backfill tabel school_classes, teaching_assignments, dan schedules jika academic_year_id masih NULL
        DB::table('school_classes')
            ->whereNull('academic_year_id')
            ->where('created_at', '<', '2026-07-01')
            ->update([
                'academic_year_id' => 1,
                'semester_id' => 1,
            ]);

        DB::table('teaching_assignments')
            ->whereNull('academic_year_id')
            ->where('created_at', '<', '2026-07-01')
            ->update([
                'academic_year_id' => 1,
                'semester_id' => 1,
            ]);

        DB::table('schedules')
            ->whereNull('academic_year_id')
            ->where('created_at', '<', '2026-07-01')
            ->update([
                'academic_year_id' => 1,
                'semester_id' => 1,
            ]);

        // 2. Pastikan kelas-kelas historis Tahun Ajaran 2025/2026 tersedia di database
        $historicalClasses = [
            1 => ['name' => 'Kelas 7A', 'level_id' => 1, 'teacher_id' => 2],
            2 => ['name' => 'Kelas 9A', 'level_id' => 3, 'teacher_id' => 1],
            3 => ['name' => 'Kelas 8A', 'level_id' => 2, 'teacher_id' => 4],
            4 => ['name' => 'Kelas 7B', 'level_id' => 1, 'teacher_id' => 3],
            5 => ['name' => 'Kelas 7C', 'level_id' => 1, 'teacher_id' => 5],
            7 => ['name' => 'X-1', 'level_id' => 1, 'teacher_id' => 15],
            8 => ['name' => 'X-2', 'level_id' => 1, 'teacher_id' => 16],
            9 => ['name' => 'XI-1', 'level_id' => 2, 'teacher_id' => 17],
        ];

        foreach ($historicalClasses as $id => $classData) {
            $existingClass = DB::table('school_classes')->where('id', $id)->first();
            if (!$existingClass) {
                DB::table('school_classes')->insert([
                    'id' => $id,
                    'name' => $classData['name'],
                    'level_id' => $classData['level_id'],
                    'teacher_id' => $classData['teacher_id'],
                    'academic_year_id' => 1,
                    'semester_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('school_classes')->where('id', $id)->update([
                    'academic_year_id' => 1,
                    'semester_id' => 1,
                ]);
            }
        }

        // 3. Sinkronisasi schedules.school_class_id dari teaching_assignments
        $schedules = DB::table('schedules as s')
            ->join('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
            ->whereNull('s.school_class_id')
            ->select('s.id as schedule_id', 'ta.school_class_id')
            ->get();

        foreach ($schedules as $item) {
            DB::table('schedules')
                ->where('id', $item->schedule_id)
                ->update(['school_class_id' => $item->school_class_id]);
        }

        // 4. Rekonstruksi relasi siswa ke kelas (class_student) untuk Tahun 2025/2026
        $classStudentsYear1 = [
            1 => [1, 2, 3, 5, 6, 43, 44, 45, 72, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95],
            2 => [7, 8, 9, 10, 11, 12, 13, 14],
            3 => [15, 16, 17, 18, 19, 20, 21, 22],
            4 => [23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35],
            5 => [46, 47, 48, 49, 51, 52, 53, 54, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 96, 97, 98, 99, 100, 101, 102],
            7 => [125, 126, 127, 128, 129, 130, 131, 132, 133, 134],
            8 => [135, 136, 137, 138, 139, 140, 141, 142, 143, 144],
            9 => [145, 146, 147, 148, 149, 150, 151, 152, 153, 154],
        ];

        $semestersYear1 = [1, 2];

        foreach ($semestersYear1 as $semesterId) {
            foreach ($classStudentsYear1 as $classId => $studentIds) {
                foreach ($studentIds as $studentId) {
                    $studentExists = DB::table('students')->where('id', $studentId)->exists();
                    if (!$studentExists) {
                        continue;
                    }

                    DB::table('class_student')->updateOrInsert(
                        [
                            'student_id' => $studentId,
                            'semester_id' => $semesterId,
                        ],
                        [
                            'school_class_id' => $classId,
                            'academic_year_id' => 1,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('class_student')->whereIn('semester_id', [1, 2])->delete();
    }
};
