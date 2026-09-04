<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Semester;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Dapatkan Tahun Ajaran 2025/2026 secara dinamis
        $year2025 = AcademicYear::where('name', 'like', '%2025%')->first() ?? AcademicYear::orderBy('id')->first();
        if (!$year2025) {
            return;
        }

        $semesters2025 = Semester::where('academic_year_id', $year2025->id)->get();
        if ($semesters2025->isEmpty()) {
            return;
        }

        // 2. Backfill tabel school_classes, teaching_assignments, dan schedules jika academic_year_id masih NULL secara aman
        $nullClasses = DB::table('school_classes')->whereNull('academic_year_id')->get();
        foreach ($nullClasses as $nc) {
            $existingClass = DB::table('school_classes')
                ->where('name', $nc->name)
                ->where('academic_year_id', $year2025->id)
                ->where('id', '!=', $nc->id)
                ->first();

            if ($existingClass) {
                DB::table('students')->where('school_class_id', $nc->id)->update(['school_class_id' => $existingClass->id]);
                DB::table('teaching_assignments')->where('school_class_id', $nc->id)->update(['school_class_id' => $existingClass->id]);
                DB::table('schedules')->where('school_class_id', $nc->id)->update(['school_class_id' => $existingClass->id]);
                try {
                    DB::table('school_classes')->where('id', $nc->id)->delete();
                } catch (\Throwable $e) {
                    DB::table('school_classes')->where('id', $nc->id)->update(['name' => $nc->name . ' (archived-' . $nc->id . ')']);
                }
            } else {
                try {
                    DB::table('school_classes')->where('id', $nc->id)->update([
                        'academic_year_id' => $year2025->id,
                        'semester_id' => $semesters2025->first()->id,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        try {
            DB::table('teaching_assignments')
                ->whereNull('academic_year_id')
                ->where('created_at', '<', '2026-07-01')
                ->update([
                    'academic_year_id' => $year2025->id,
                    'semester_id' => $semesters2025->first()->id,
                ]);
        } catch (\Throwable $e) {}

        try {
            DB::table('schedules')
                ->whereNull('academic_year_id')
                ->where('created_at', '<', '2026-07-01')
                ->update([
                    'academic_year_id' => $year2025->id,
                    'semester_id' => $semesters2025->first()->id,
                ]);
        } catch (\Throwable $e) {}

        // 3. Mapping dinamis kelas ke daftar NIS siswa
        $classMapping = [
            'Kelas 9A' => ['1200', '1201', '1202', '1203', '1204', '1205', '1206', '1207'],
            'Kelas 8A' => ['1208', '1209', '1210', '1211', '1212', '1213', '1214', '1215'],
            'Kelas 7B' => ['1216', '1217', '1218', '1219', '1220', '1221', '1222', '1223', '1224', '1225', '1226', '1227', '1228'],
            'Kelas 7A' => ['1001', '1002', '1003', '1004', '1005', '1010', '1301', '1400', '1401', '20240027', '20240028', '20240029', '20240030', '20240031', '20240032', '20240033', '20240034', '20240035', '20240036', '20240037', '20240038', '20240039', '20240040', '20240041', '20240042', '20240043', '20240044', '20240045', '20240046', '20240047', '20240048', '20240049', '20240050'],
            'Kelas 7C' => ['20240001', '20240002', '20240003', '20240004', '20240005', '20240006', '20240007', '20240008', '20240009', '20240010', '20240011', '20240012', '20240013', '20240014', '20240015', '20240016', '20240017', '20240018', '20240019', '20240020', '20240021', '20240022', '20240023', '20240024', '20240025', '20240026', '3121539431', '0127573197', '0123309910', '0124534914', '0128316927', '0114768421', '0117970706'],
            'X-1' => ['20250101', '20250102', '20250103', '20250104', '20250105', '20250106', '20250107', '20250108', '20250109', '20250110'],
            'X-2' => ['20250201', '20250202', '20250203', '20250204', '20250205', '20250206', '20250207', '20250208', '20250209', '20250210'],
            'XI-1' => ['20250301', '20250302', '20250303', '20250304', '20250305', '20250306', '20250307', '20250308', '20250309', '20250310'],
        ];

        foreach ($classMapping as $className => $nisList) {
            $class = SchoolClass::withoutGlobalScopes()
                ->where('name', $className)
                ->where(function($q) use ($year2025) {
                    $q->where('academic_year_id', $year2025->id)
                      ->orWhereNull('academic_year_id')
                      ->orWhere('created_at', '<', '2026-07-01');
                })
                ->first();

            if (!$class) {
                $class = SchoolClass::create([
                    'name' => $className,
                    'level_id' => str_contains($className, '7') || str_contains($className, 'X-') ? 1 : (str_contains($className, '8') || str_contains($className, 'XI-') ? 2 : 3),
                    'academic_year_id' => $year2025->id,
                    'semester_id' => $semesters2025->first()->id,
                ]);
            } else {
                if ($class->academic_year_id != $year2025->id) {
                    DB::table('school_classes')->where('id', $class->id)->update(['academic_year_id' => $year2025->id]);
                }
            }

            $students = Student::whereIn('nis', $nisList)
                ->orWhere('school_class_id', $class->id)
                ->get();

            foreach ($semesters2025 as $sem) {
                foreach ($students as $st) {
                    DB::table('class_student')->updateOrInsert(
                        [
                            'student_id' => $st->id,
                            'semester_id' => $sem->id,
                        ],
                        [
                            'school_class_id' => $class->id,
                            'academic_year_id' => $year2025->id,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }
        }

        // 4. Sinkronisasi schedules.school_class_id dari teaching_assignments
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $year2025 = AcademicYear::where('name', 'like', '%2025%')->first();
        if ($year2025) {
            DB::table('class_student')->where('academic_year_id', $year2025->id)->delete();
        }
    }
};
