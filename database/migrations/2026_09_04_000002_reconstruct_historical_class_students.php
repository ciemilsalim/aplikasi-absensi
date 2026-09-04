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
        // 1. Sinkronisasi schedules.school_class_id dari teaching_assignments
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

        // 2. Daftar siswa per kelas di Tahun Ajaran 2025/2026 (Academic Year ID = 1)
        // Berdasarkan jejak riwayat mutasi (student_class_histories), presensi mapel, dan presensi harian
        $classStudentsYear1 = [
            // Kelas 7A (ID: 1)
            1 => [1, 2, 3, 5, 6, 43, 44, 45, 72, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95],
            // Kelas 9A (ID: 2) - Termasuk siswa yang sudah lulus (ID 7 & 8)
            2 => [7, 8, 9, 10, 11, 12, 13, 14],
            // Kelas 8A (ID: 3)
            3 => [15, 16, 17, 18, 19, 20, 21, 22],
            // Kelas 7B (ID: 4) - Siswa yang naik kelas ke 8A di 2026/2027
            4 => [23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35],
            // Kelas 7C (ID: 5)
            5 => [46, 47, 48, 49, 51, 52, 53, 54, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 96, 97, 98, 99, 100, 101, 102],
            // X-1 (ID: 7)
            7 => [125, 126, 127, 128, 129, 130, 131, 132, 133, 134],
            // X-2 (ID: 8)
            8 => [135, 136, 137, 138, 139, 140, 141, 142, 143, 144],
            // XI-1 (ID: 9)
            9 => [145, 146, 147, 148, 149, 150, 151, 152, 153, 154],
        ];

        $now = now();

        // Rekonstruksi class_student untuk Semester 1 (2025/2026 Genap) dan Semester 2 (2025/2026 Ganjil)
        $semestersYear1 = [1, 2];

        foreach ($semestersYear1 as $semesterId) {
            foreach ($classStudentsYear1 as $classId => $studentIds) {
                foreach ($studentIds as $studentId) {
                    // Validasi siswa ada di tabel students
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
        // Rollback data rekonstruksi semester 1 dan 2
        DB::table('class_student')->whereIn('semester_id', [1, 2])->delete();
    }
};
