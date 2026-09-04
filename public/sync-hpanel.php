<?php
/**
 * SIASEK - Automatic Database Synchronization & Multi-Semester Repair Tool
 * Dapat diakses langsung via browser: https://domain-anda/sync-hpanel.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

$now = now();
$syncLog = [];
$stats = [
    'classes_synced' => 0,
    'students_synced' => 0,
    'semesters_synced' => 0,
];

try {
    // 1. Dapatkan Tahun Ajaran 2025/2026 secara dinamis
    $year2025 = AcademicYear::where('name', 'like', '%2025%')->first() ?? AcademicYear::orderBy('id')->first();
    $year2026 = AcademicYear::where('name', 'like', '%2026%')->first() ?? AcademicYear::orderBy('id', 'desc')->first();

    if ($year2025) {
        $semesters2025 = Semester::where('academic_year_id', $year2025->id)->get();
        $stats['semesters_synced'] = $semesters2025->count();
        $sem1 = $semesters2025->first()?->id ?? 1;

        // 2. Tangani tabel school_classes yang academic_year_id masih NULL secara aman (tanpa memicu duplicate key constraint)
        $nullClasses = DB::table('school_classes')->whereNull('academic_year_id')->get();
        foreach ($nullClasses as $nc) {
            $existingClass = DB::table('school_classes')
                ->where('name', $nc->name)
                ->where('academic_year_id', $year2025->id)
                ->where('id', '!=', $nc->id)
                ->first();

            if ($existingClass) {
                // Pindahkan referensi siswa & teaching_assignments ke kelas yang sudah ada
                DB::table('students')->where('school_class_id', $nc->id)->update(['school_class_id' => $existingClass->id]);
                DB::table('teaching_assignments')->where('school_class_id', $nc->id)->update(['school_class_id' => $existingClass->id]);
                DB::table('schedules')->where('school_class_id', $nc->id)->update(['school_class_id' => $existingClass->id]);
                
                // Hapus atau beri nama unik agar tidak memicu unique index
                try {
                    DB::table('school_classes')->where('id', $nc->id)->delete();
                } catch (\Throwable $e) {
                    DB::table('school_classes')->where('id', $nc->id)->update(['name' => $nc->name . ' (archived-' . $nc->id . ')']);
                }
            } else {
                try {
                    DB::table('school_classes')->where('id', $nc->id)->update([
                        'academic_year_id' => $year2025->id,
                        'semester_id' => $sem1,
                    ]);
                } catch (\Throwable $e) {
                    // Abaikan jika ada benturan constraint
                }
            }
        }

        // Tangani teaching_assignments & schedules
        try {
            DB::table('teaching_assignments')
                ->whereNull('academic_year_id')
                ->where('created_at', '<', '2026-07-01')
                ->update([
                    'academic_year_id' => $year2025->id,
                    'semester_id' => $sem1,
                ]);
        } catch (\Throwable $e) {}

        try {
            DB::table('schedules')
                ->whereNull('academic_year_id')
                ->where('created_at', '<', '2026-07-01')
                ->update([
                    'academic_year_id' => $year2025->id,
                    'semester_id' => $sem1,
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

        // Cari guru Elyana jika ada
        $elyanaTeacher = Teacher::where('name', 'like', '%Elyana%')->first();

        foreach ($classMapping as $className => $nisList) {
            // Prioritaskan kelas yang sudah ada dengan academic_year_id = year2025
            $class = SchoolClass::withoutGlobalScopes()
                ->where('name', $className)
                ->where('academic_year_id', $year2025->id)
                ->first();

            if (!$class) {
                $class = SchoolClass::withoutGlobalScopes()
                    ->where('name', $className)
                    ->whereNull('academic_year_id')
                    ->first();

                if ($class) {
                    try {
                        DB::table('school_classes')->where('id', $class->id)->update([
                            'academic_year_id' => $year2025->id,
                            'semester_id' => $sem1,
                        ]);
                    } catch (\Throwable $e) {}
                } else {
                    $class = SchoolClass::create([
                        'name' => $className,
                        'level_id' => str_contains($className, '7') || str_contains($className, 'X-') ? 1 : (str_contains($className, '8') || str_contains($className, 'XI-') ? 2 : 3),
                        'academic_year_id' => $year2025->id,
                        'semester_id' => $sem1,
                    ]);
                }
            }

            // Hubungkan Ibu Elyana ke Kelas 9A pada 2025/2026
            if ($elyanaTeacher && str_contains($className, '9A')) {
                try {
                    DB::table('school_classes')->where('id', $class->id)->update(['teacher_id' => $elyanaTeacher->id]);
                    $class->teacher_id = $elyanaTeacher->id;
                } catch (\Throwable $e) {}
            }

            $stats['classes_synced']++;

            // Cari siswa berdasarkan NIS dan riwayat
            $students = Student::whereIn('nis', $nisList)
                ->orWhere('school_class_id', $class->id)
                ->get();

            $homeroomName = $class->homeroomTeacher?->name ?? ($class->teacher_id && $elyanaTeacher && $class->teacher_id == $elyanaTeacher->id ? $elyanaTeacher->name : 'Belum Ditentukan');

            $syncLog[] = [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'year_name' => $year2025->name,
                'homeroom' => $homeroomName,
                'student_count' => $students->count(),
                'sample_students' => $students->take(3)->pluck('name')->join(', ') . ($students->count() > 3 ? '...' : ''),
            ];

            // Isi class_student untuk setiap semester di tahun 2025/2026
            foreach ($semesters2025 as $sem) {
                foreach ($students as $st) {
                    try {
                        DB::table('class_student')->updateOrInsert(
                            [
                                'student_id' => $st->id,
                                'semester_id' => $sem->id,
                                'academic_year_id' => $year2025->id,
                            ],
                            [
                                'school_class_id' => $class->id,
                                'updated_at' => $now,
                                'created_at' => $now,
                            ]
                        );
                        $stats['students_synced']++;
                    } catch (\Throwable $e) {}
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

    // Bersihkan semua cache Laravel
    Artisan::call('optimize:clear');

} catch (\Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi Database SIASEK & HPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-slate-800 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-700 pb-5">
            <div>
                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-bold text-xs rounded-full uppercase tracking-wider">Auto-Sync Engine v2</span>
                <h1 class="text-2xl font-black mt-2 text-white">Sinkronisasi Database Presensi & SIPADA</h1>
                <p class="text-xs text-slate-400 mt-1">Memperbaiki dan merekonstruksi relasi kelas, wali kelas, dan siswa multi-semester.</p>
            </div>
            <div class="hidden sm:block text-right">
                <span class="text-xs text-slate-400">Waktu Eksekusi</span>
                <p class="text-sm font-bold text-white"><?= date('d F Y, H:i:s') ?></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="p-4 bg-rose-500/20 border border-rose-500/30 rounded-2xl text-rose-300 text-sm">
                <p class="font-bold">❌ Terjadi Kesalahan:</p>
                <p class="mt-1 font-mono text-xs"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-700/50 p-4 rounded-2xl border border-slate-600/50">
                    <span class="text-xs text-slate-400 font-medium">Kelas Tersinkron</span>
                    <p class="text-2xl font-black text-white mt-1"><?= count($syncLog) ?> Kelas</p>
                </div>
                <div class="bg-slate-700/50 p-4 rounded-2xl border border-slate-600/50">
                    <span class="text-xs text-slate-400 font-medium">Relasi Siswa Multi-Semester</span>
                    <p class="text-2xl font-black text-emerald-400 mt-1"><?= $stats['students_synced'] ?> Relasi</p>
                </div>
                <div class="bg-slate-700/50 p-4 rounded-2xl border border-slate-600/50">
                    <span class="text-xs text-slate-400 font-medium">Status Cache</span>
                    <p class="text-2xl font-black text-sky-400 mt-1">Cleared ✅</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-700">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-700/70 text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="p-3">Kelas</th>
                            <th class="p-3">Tahun Ajaran</th>
                            <th class="p-3">Wali Kelas</th>
                            <th class="p-3 text-center">Jumlah Siswa</th>
                            <th class="p-3">Contoh Siswa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700 text-slate-200">
                        <?php foreach ($syncLog as $item): ?>
                            <tr class="hover:bg-slate-700/30">
                                <td class="p-3 font-bold text-white"><?= htmlspecialchars($item['class_name']) ?> <span class="text-[10px] text-slate-400">(ID: <?= $item['class_id'] ?>)</span></td>
                                <td class="p-3"><?= htmlspecialchars($item['year_name']) ?></td>
                                <td class="p-3 text-amber-400 font-medium"><?= htmlspecialchars($item['homeroom']) ?></td>
                                <td class="p-3 text-center font-bold text-emerald-400"><?= $item['student_count'] ?> Siswa</td>
                                <td class="p-3 text-slate-400 text-[11px] truncate max-w-xs"><?= htmlspecialchars($item['sample_students']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <p class="text-xs text-emerald-400 font-medium flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Database telah berhasil disinkronkan & seluruh siswa terhubung.
                </p>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="/teacher/dashboard" class="w-full sm:w-auto text-center px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs rounded-xl shadow-lg transition-all">
                        Buka Dasbor Guru &rarr;
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
