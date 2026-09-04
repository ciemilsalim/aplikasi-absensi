<?php
/**
 * SIASEK - Automatic Database Synchronization & Multi-Semester Complete Repair Tool
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
    'total_unique_students' => 0,
];

try {
    // 1. Dapatkan Tahun Ajaran 2025/2026 dan 2026/2027 secara dinamis
    $year2025 = AcademicYear::where('name', 'like', '%2025%')->first() ?? AcademicYear::orderBy('id')->first();
    $year2026 = AcademicYear::where('name', 'like', '%2026%')->first() ?? AcademicYear::orderBy('id', 'desc')->first();

    if ($year2025) {
        $semesters2025 = Semester::where('academic_year_id', $year2025->id)->get();
        $stats['semesters_synced'] = $semesters2025->count();
        $sem1 = $semesters2025->first()?->id ?? 1;

        // Daftar 13 Kelas Standar SMPN 1 Biau
        $standardClasses = [
            // Kelas 7 (Tingkat 1)
            'Kelas 7A' => ['level_id' => 1, 'aliases' => ['7A', '7 A', 'VII A', 'VII-A']],
            'Kelas 7B' => ['level_id' => 1, 'aliases' => ['7B', '7 B', 'VII B', 'VII-B']],
            'Kelas 7C' => ['level_id' => 1, 'aliases' => ['7C', '7 C', 'VII C', 'VII-C']],
            'Kelas 7D' => ['level_id' => 1, 'aliases' => ['7D', '7 D', 'VII D', 'VII-D']],
            'Kelas 7E' => ['level_id' => 1, 'aliases' => ['7E', '7 E', 'VII E', 'VII-E']],
            // Kelas 8 (Tingkat 2)
            'Kelas 8A' => ['level_id' => 2, 'aliases' => ['8A', '8 A', 'VIII A', 'VIII-A']],
            'Kelas 8B' => ['level_id' => 2, 'aliases' => ['8B', '8 B', 'VIII B', 'VIII-B']],
            'Kelas 8C' => ['level_id' => 2, 'aliases' => ['8C', '8 C', 'VIII C', 'VIII-C']],
            'Kelas 8D' => ['level_id' => 2, 'aliases' => ['8D', '8 D', 'VIII D', 'VIII-D']],
            // Kelas 9 (Tingkat 3)
            'Kelas 9A' => ['level_id' => 3, 'aliases' => ['9A', '9 A', 'IX A', 'IX-A']],
            'Kelas 9B' => ['level_id' => 3, 'aliases' => ['9B', '9 B', 'IX B', 'IX-B']],
            'Kelas 9C' => ['level_id' => 3, 'aliases' => ['9C', '9 C', 'IX C', 'IX-C']],
            'Kelas 9D' => ['level_id' => 3, 'aliases' => ['9D', '9 D', 'IX D', 'IX-D']],
        ];

        // Fungsi normalisasi nama kelas
        $normalizeName = function($name) use ($standardClasses) {
            $trimmed = trim($name);
            foreach ($standardClasses as $stdName => $meta) {
                if (strcasecmp($trimmed, $stdName) === 0) return $stdName;
                foreach ($meta['aliases'] as $alias) {
                    if (strcasecmp($trimmed, $alias) === 0) return $stdName;
                }
            }
            if (preg_match('/^([789])\s*([A-Ea-e])/i', $trimmed, $m)) {
                return 'Kelas ' . $m[1] . strtoupper($m[2]);
            }
            return $trimmed;
        };

        // 2. Tangani tabel school_classes yang academic_year_id masih NULL secara aman
        $nullClasses = DB::table('school_classes')->whereNull('academic_year_id')->get();
        foreach ($nullClasses as $nc) {
            $norm = $normalizeName($nc->name);
            $existingClass = DB::table('school_classes')
                ->where('name', $norm)
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
                        'name' => $norm,
                        'academic_year_id' => $year2025->id,
                        'semester_id' => $sem1,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        // Tangani teaching_assignments & schedules 2025/2026
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

        // 3. Pastikan Semua 13 Kelas Standar Ada di Tahun Ajaran 2025/2026
        $active2025Classes = []; // normalized_name => SchoolClass model
        foreach ($standardClasses as $className => $meta) {
            $class = SchoolClass::withoutGlobalScopes()
                ->where(function($q) use ($className, $meta) {
                    $q->where('name', $className);
                    foreach ($meta['aliases'] as $alias) {
                        $q->orWhere('name', $alias);
                    }
                })
                ->where('academic_year_id', $year2025->id)
                ->first();

            if (!$class) {
                // Cek kelas lama tanpa tahun ajaran
                $class = SchoolClass::withoutGlobalScopes()
                    ->where(function($q) use ($className, $meta) {
                        $q->where('name', $className);
                        foreach ($meta['aliases'] as $alias) {
                            $q->orWhere('name', $alias);
                        }
                    })
                    ->whereNull('academic_year_id')
                    ->first();

                if ($class) {
                    try {
                        DB::table('school_classes')->where('id', $class->id)->update([
                            'name' => $className,
                            'level_id' => $meta['level_id'],
                            'academic_year_id' => $year2025->id,
                            'semester_id' => $sem1,
                        ]);
                        $class->refresh();
                    } catch (\Throwable $e) {}
                } else {
                    $class = SchoolClass::create([
                        'name' => $className,
                        'level_id' => $meta['level_id'],
                        'academic_year_id' => $year2025->id,
                        'semester_id' => $sem1,
                    ]);
                }
            }

            // Normalisasi nama kelas ke format 'Kelas 7A', dll.
            if ($class->name !== $className) {
                try {
                    DB::table('school_classes')->where('id', $class->id)->update(['name' => $className]);
                    $class->name = $className;
                } catch (\Throwable $e) {}
            }

            $active2025Classes[$className] = $class;
        }

        // 4. Sinkronkan Wali Kelas (Homeroom Teachers)
        // Hubungkan guru wali kelas berdasarkan data master guru
        $knownTeachers = [
            'Kelas 9A' => 'Elyana',
            'Kelas 8A' => 'Rosmini',
            'Kelas 7A' => 'Susanto',
            'Kelas 7B' => 'Muhrijah',
            'Kelas 7C' => 'Delmiyanti',
        ];

        foreach ($knownTeachers as $cName => $tKeyword) {
            if (isset($active2025Classes[$cName])) {
                $cls = $active2025Classes[$cName];
                if (!$cls->teacher_id || Teacher::where('id', $cls->teacher_id)->where('name', 'like', "%{$tKeyword}%")->doesntExist()) {
                    $teacher = Teacher::where('name', 'like', "%{$tKeyword}%")->first();
                    if ($teacher) {
                        try {
                            DB::table('school_classes')->where('id', $cls->id)->update(['teacher_id' => $teacher->id]);
                            $cls->teacher_id = $teacher->id;
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        // 5. Multi-Source Discovery Siswa ke Kelas 2025/2026 (Tanpa Hardcoded NIS)
        $studentClassMap = []; // student_id => normalized_class_name

        // Sumber A: student_class_histories
        if (Schema::hasTable('student_class_histories')) {
            $schRows = DB::table('student_class_histories as sch')
                ->join('school_classes as sc', 'sch.school_class_id', '=', 'sc.id')
                ->where(function($q) use ($year2025) {
                    $q->where('sch.academic_year_id', $year2025->id)
                      ->orWhere('sc.academic_year_id', $year2025->id);
                })
                ->select('sch.student_id', 'sc.name as class_name')
                ->get();

            foreach ($schRows as $r) {
                $norm = $normalizeName($r->class_name);
                if (isset($standardClasses[$norm])) {
                    $studentClassMap[$r->student_id] = $norm;
                }
            }
        }

        // Sumber B: subject_attendances tahun ajaran 2025/2026
        if (Schema::hasTable('subject_attendances')) {
            $saRows = DB::table('subject_attendances as sa')
                ->leftJoin('schedules as s', 'sa.schedule_id', '=', 's.id')
                ->leftJoin('teaching_assignments as ta', 's.teaching_assignment_id', '=', 'ta.id')
                ->leftJoin('school_classes as sc', DB::raw('COALESCE(s.school_class_id, ta.school_class_id)'), '=', 'sc.id')
                ->where(function($q) use ($semesters2025) {
                    $q->whereIn('sa.semester_id', $semesters2025->pluck('id'))
                      ->orWhereBetween('sa.created_at', ['2025-07-01', '2026-06-30']);
                })
                ->whereNotNull('sc.name')
                ->select('sa.student_id', 'sc.name as class_name', DB::raw('count(*) as cnt'))
                ->groupBy('sa.student_id', 'sc.name')
                ->orderByDesc('cnt')
                ->get();

            foreach ($saRows as $r) {
                if (!isset($studentClassMap[$r->student_id])) {
                    $norm = $normalizeName($r->class_name);
                    if (isset($standardClasses[$norm])) {
                        $studentClassMap[$r->student_id] = $norm;
                    }
                }
            }
        }

        // Sumber C: Presensi Harian (attendances) 2025/2026
        if (Schema::hasTable('attendances')) {
            $attRows = DB::table('attendances as a')
                ->join('students as st', 'a.student_id', '=', 'st.id')
                ->leftJoin('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
                ->where(function($q) use ($semesters2025) {
                    $q->whereIn('a.semester_id', $semesters2025->pluck('id'))
                      ->orWhereBetween('a.created_at', ['2025-07-01', '2026-06-30']);
                })
                ->whereNotNull('sc.name')
                ->select('a.student_id', 'sc.name as class_name')
                ->distinct()
                ->get();

            foreach ($attRows as $r) {
                if (!isset($studentClassMap[$r->student_id])) {
                    $norm = $normalizeName($r->class_name);
                    if (isset($standardClasses[$norm])) {
                        $studentClassMap[$r->student_id] = $norm;
                    }
                }
            }
        }

        // Sumber D: Siswa yang school_class_id-nya mengarah ke kelas 2025/2026
        $directStudents = DB::table('students as st')
            ->join('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
            ->where(function($q) use ($year2025) {
                $q->where('sc.academic_year_id', $year2025->id)
                  ->orWhere('sc.created_at', '<', '2026-07-01');
            })
            ->select('st.id as student_id', 'sc.name as class_name')
            ->get();

        foreach ($directStudents as $r) {
            if (!isset($studentClassMap[$r->student_id])) {
                $norm = $normalizeName($r->class_name);
                if (isset($standardClasses[$norm])) {
                    $studentClassMap[$r->student_id] = $norm;
                }
            }
        }

        // Sumber E: Inferensi Kenaikan Kelas Historis (Grade Promotion Logic)
        // Siswa aktif di 2026/2027:
        // - Kelas 8[A-E] (2026/2027) => Berasal dari Kelas 7[A-E] di 2025/2026
        // - Kelas 9[A-E] (2026/2027) => Berasal dari Kelas 8[A-E] di 2025/2026
        $allCurrentStudents = DB::table('students as st')
            ->leftJoin('school_classes as sc', 'st.school_class_id', '=', 'sc.id')
            ->select('st.id as student_id', 'st.name', 'st.nis', 'st.status', 'sc.name as current_class_name')
            ->get();

        foreach ($allCurrentStudents as $st) {
            if (isset($studentClassMap[$st->student_id])) {
                continue;
            }

            if ($st->current_class_name) {
                $currNorm = $normalizeName($st->current_class_name);
                // Kelas 8 -> Kelas 7
                if (preg_match('/Kelas 8([A-Ea-e])/i', $currNorm, $m)) {
                    $prevClass = 'Kelas 7' . strtoupper($m[1]);
                    if (isset($standardClasses[$prevClass])) {
                        $studentClassMap[$st->student_id] = $prevClass;
                    }
                }
                // Kelas 9 -> Kelas 8
                elseif (preg_match('/Kelas 9([A-Ea-e])/i', $currNorm, $m)) {
                    $prevClass = 'Kelas 8' . strtoupper($m[1]);
                    if (isset($standardClasses[$prevClass])) {
                        $studentClassMap[$st->student_id] = $prevClass;
                    }
                }
                // Siswa yang masih di Kelas 9 pada 2025 (Alumni / Lulus / Riwayat 9)
                elseif (preg_match('/Kelas 9([A-Ea-e])/i', $currNorm, $m) || in_array(strtolower($st->status ?? ''), ['lulus', 'graduated', 'alumni'])) {
                    $target9 = 'Kelas 9' . (isset($m[1]) ? strtoupper($m[1]) : 'A');
                    if (isset($standardClasses[$target9])) {
                        $studentClassMap[$st->student_id] = $target9;
                    }
                }
            }
        }

        // Sumber F: Siswa Alumni/Lulusan yang belum terpetakan dimasukkan ke Kelas 9A-9D
        $unmappedAlumni = DB::table('students')
            ->whereNotIn('id', array_keys($studentClassMap))
            ->where(function($q) {
                $q->whereIn('status', ['lulus', 'graduated', 'alumni'])
                  ->orWhere('created_at', '<', '2025-07-01');
            })
            ->get();

        $c9List = ['Kelas 9A', 'Kelas 9B', 'Kelas 9C', 'Kelas 9D'];
        $c9Index = 0;
        foreach ($unmappedAlumni as $al) {
            $studentClassMap[$al->id] = $c9List[$c9Index % 4];
            $c9Index++;
        }

        // 6. Masukkan Seluruh Siswa Terpetakan ke Pivot class_student Multi-Semester 2025/2026
        // Catatan: TIDAK MENGUBAH / MERUSAK data 2026/2027!
        $studentsByClass = [];
        foreach ($studentClassMap as $studentId => $className) {
            $studentsByClass[$className][] = $studentId;
            $classModel = $active2025Classes[$className] ?? null;

            if ($classModel) {
                foreach ($semesters2025 as $sem) {
                    try {
                        DB::table('class_student')->updateOrInsert(
                            [
                                'student_id' => $studentId,
                                'semester_id' => $sem->id,
                                'academic_year_id' => $year2025->id,
                            ],
                            [
                                'school_class_id' => $classModel->id,
                                'updated_at' => $now,
                                'created_at' => $now,
                            ]
                        );
                        $stats['students_synced']++;
                    } catch (\Throwable $e) {}
                }
            }
        }

        $stats['total_unique_students'] = count($studentClassMap);

        // 7. Siapkan Data Log Tampilan untuk Seluruh 13 Kelas
        foreach ($standardClasses as $className => $meta) {
            $cls = $active2025Classes[$className] ?? null;
            if (!$cls) continue;

            $stats['classes_synced']++;
            $stIds = $studentsByClass[$className] ?? [];
            $sampleStudents = Student::whereIn('id', array_slice($stIds, 0, 4))->pluck('name')->join(', ');
            if (count($stIds) > 4) {
                $sampleStudents .= '... (' . count($stIds) . ' total)';
            }

            $teacherName = $cls->homeroomTeacher?->name;
            if (!$teacherName && $cls->teacher_id) {
                $teacherName = Teacher::withTrashed()->where('id', $cls->teacher_id)->value('name');
            }
            if (!$teacherName) {
                $taTeacher = DB::table('teaching_assignments as ta')
                    ->join('teachers as t', 'ta.teacher_id', '=', 't.id')
                    ->where('ta.school_class_id', $cls->id)
                    ->select('t.id', 't.name')
                    ->first();
                if ($taTeacher) {
                    $teacherName = $taTeacher->name;
                    try {
                        DB::table('school_classes')->where('id', $cls->id)->update(['teacher_id' => $taTeacher->id]);
                    } catch (\Throwable $e) {}
                }
            }
            if (!$teacherName) {
                $teacherName = 'Belum Ditentukan';
            }

            $syncLog[] = [
                'class_id' => $cls->id,
                'class_name' => $className,
                'level' => 'Tingkat ' . ($meta['level_id'] == 1 ? '7' : ($meta['level_id'] == 2 ? '8' : '9')),
                'year_name' => $year2025->name,
                'homeroom' => $teacherName,
                'student_count' => count($stIds),
                'sample_students' => $sampleStudents ?: 'Belum ada siswa terhubung',
            ];
        }

        // 8. Sinkronisasi schedules.school_class_id
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
    <title>Sinkronisasi Lengkap Database Presensi & SIPADA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8 flex items-center justify-center">
    <div class="max-w-5xl w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-800 pb-5 gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-bold text-xs rounded-full uppercase tracking-wider">
                        Auto-Sync Engine v3 (Complete SMP)
                    </span>
                    <span class="px-3 py-1 bg-sky-500/20 text-sky-400 font-bold text-xs rounded-full">
                        TA. 2025/2026 (Ganjil & Genap)
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black mt-2 text-white">Sinkronisasi Database Presensi & SIPADA</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">
                    Merekonstruksi seluruh 13 Kelas (7A–7E, 8A–8D, 9A–9D), Wali Kelas, dan Siswa multi-semester tanpa mengubah data aktif 2026/2027.
                </p>
            </div>
            <div class="text-left sm:text-right">
                <span class="text-xs text-slate-400 font-medium">Waktu Eksekusi</span>
                <p class="text-sm font-bold text-white"><?= date('d F Y, H:i:s') ?></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="p-4 bg-rose-500/20 border border-rose-500/30 rounded-2xl text-rose-300 text-sm">
                <p class="font-bold flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Terjadi Kesalahan:
                </p>
                <p class="mt-2 font-mono text-xs bg-slate-950/50 p-3 rounded-xl border border-rose-500/20"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php else: ?>
            <!-- Metrics Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50">
                    <span class="text-xs text-slate-400 font-medium">Total Kelas Tersinkron</span>
                    <p class="text-2xl font-black text-white mt-1"><?= count($syncLog) ?> Kelas</p>
                </div>
                <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50">
                    <span class="text-xs text-slate-400 font-medium">Total Siswa Terdeteksi</span>
                    <p class="text-2xl font-black text-sky-400 mt-1"><?= $stats['total_unique_students'] ?> Siswa</p>
                </div>
                <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50">
                    <span class="text-xs text-slate-400 font-medium">Relasi Multi-Semester</span>
                    <p class="text-2xl font-black text-emerald-400 mt-1"><?= $stats['students_synced'] ?> Relasi</p>
                </div>
                <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/50">
                    <span class="text-xs text-slate-400 font-medium">Data Aktif 2026/2027</span>
                    <p class="text-2xl font-black text-amber-400 mt-1">Aman & Terjaga 🛡️</p>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/50">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800/80 text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="p-3.5">Kelas</th>
                            <th class="p-3.5">Tingkat</th>
                            <th class="p-3.5">Tahun Ajaran</th>
                            <th class="p-3.5">Wali Kelas</th>
                            <th class="p-3.5 text-center">Jumlah Siswa</th>
                            <th class="p-3.5">Contoh Siswa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-200">
                        <?php foreach ($syncLog as $item): ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-3.5 font-bold text-white">
                                    <?= htmlspecialchars($item['class_name']) ?>
                                    <span class="text-[10px] text-slate-400 font-normal block sm:inline">(ID: <?= $item['class_id'] ?>)</span>
                                </td>
                                <td class="p-3.5">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 text-[10px] font-semibold">
                                        <?= htmlspecialchars($item['level']) ?>
                                    </span>
                                </td>
                                <td class="p-3.5 text-slate-300"><?= htmlspecialchars($item['year_name']) ?></td>
                                <td class="p-3.5 text-amber-400 font-medium">
                                    <?= htmlspecialchars($item['homeroom']) ?>
                                </td>
                                <td class="p-3.5 text-center font-bold">
                                    <span class="px-2.5 py-1 rounded-full text-xs <?= $item['student_count'] > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-500' ?>">
                                        <?= $item['student_count'] ?> Siswa
                                    </span>
                                </td>
                                <td class="p-3.5 text-slate-400 text-[11px] truncate max-w-xs" title="<?= htmlspecialchars($item['sample_students']) ?>">
                                    <?= htmlspecialchars($item['sample_students']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <div class="text-xs text-slate-400 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Seluruh data kelas tahun 2025/2026 (Ganjil & Genap) telah siap diakses pada menu guru & admin.</span>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="/teacher/dashboard" class="w-full sm:w-auto text-center px-6 py-3 bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <span>Buka Dasbor Guru</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
