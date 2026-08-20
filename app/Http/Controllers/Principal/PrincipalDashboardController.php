<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Teacher;
use App\Models\TeachingJournal;
use App\Models\Schedule;
use App\Models\SubjectAttendance;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\StudentPermit;
use App\Models\SchoolClass;
use Carbon\Carbon;

class PrincipalDashboardController extends Controller
{
    /**
     * Tampilkan Executive Overview Dashboard untuk Kepala Sekolah.
     */
    public function index(Request $request)
    {
        $debugErrors = [];

        try {
            $today = Carbon::today();
            $dayOfWeekIso = $today->dayOfWeekIso; // 1 = Senin, ..., 7 = Minggu
            $dayOfWeek = $today->dayOfWeek;       // 0 = Minggu, 1 = Senin

            // 1. STATISTIK KEHADIRAN HARIAN SEKOLAH HARI INI
            $totalStudents = 0;
            $totalHadir = 0;
            $presentOnTimeCount = 0;
            $presentLateCount = 0;
            $sickCount = 0;
            $permitCount = 0;
            $alphaCount = 0;
            $totalMarked = 0;
            $unmarkedCount = 0;
            $dailyPresencePercentage = 0;
            $attendancesToday = collect();
            $activeStudentIds = collect();

            try {
                // Hanya hitung siswa dengan status 'aktif' (mengabaikan siswa lulus, pindah, tidak_aktif)
                $activeStudentsQuery = Student::query()
                    ->when(Schema::hasColumn('students', 'status'), function ($q) {
                        return $q->where('status', 'aktif');
                    });
                
                $activeStudentIds = (clone $activeStudentsQuery)->pluck('id');
                $totalStudents = (clone $activeStudentsQuery)->count();

                $attendancesToday = Attendance::whereDate('attendance_time', $today)
                    ->whereIn('student_id', $activeStudentIds)
                    ->when(session('active_semester_id'), function ($q) {
                        return $q->where('semester_id', session('active_semester_id'));
                    })
                    ->get();

                $presentOnTimeCount = $attendancesToday->whereIn('status', ['tepat_waktu', 'on_time'])->count();
                $presentLateCount = $attendancesToday->whereIn('status', ['terlambat', 'late'])->count();
                $generalHadirCount = $attendancesToday->whereIn('status', ['hadir', 'present'])->count();
                $totalHadir = $presentOnTimeCount + $presentLateCount + $generalHadirCount;
                
                $sickCount = $attendancesToday->where('status', 'sakit')->count();
                $permitCount = $attendancesToday->where('status', 'izin')->count();
                $alphaCount = $attendancesToday->where('status', 'alpa')->count();
                $totalMarked = $attendancesToday->count();
                $unmarkedCount = max(0, $totalStudents - $totalMarked);

                $dailyPresencePercentage = $totalStudents > 0 
                    ? round(($totalHadir / $totalStudents) * 100, 1) 
                    : 0;
            } catch (\Throwable $e) {
                $debugErrors['attendance_daily'] = $e->getMessage();
                Log::warning('[PrincipalDashboard] Gagal memuat kehadiran harian: ' . $e->getMessage());
            }

            // Siswa sedang izin keluar sekolah (Fail-safe check)
            $activePermitsCount = 0;
            if (Schema::hasTable('student_permits')) {
                try {
                    $activePermitsCount = StudentPermit::whereDate('time_out', $today)
                        ->whereIn('student_id', $activeStudentIds)
                        ->whereNull('time_in')
                        ->count();
                } catch (\Throwable $e) {
                    $debugErrors['student_permits'] = $e->getMessage();
                }
            }

            // 2. STATISTIK SESI MAPEL HARI INI
            $totalMapelSessionsToday = 0;
            $activeMapelSessionsToday = 0;
            $mapelHadirCount = 0;
            $mapelBolosCount = 0;

            try {
                $schedulesTodayMapel = Schedule::where(function ($q) use ($dayOfWeekIso, $dayOfWeek) {
                        $q->where('day_of_week', $dayOfWeekIso)
                          ->orWhere('day_of_week', $dayOfWeek);
                    })
                    ->when(Schema::hasColumn('schedules', 'schedule_type'), function ($q) {
                        return $q->where('schedule_type', 'regular');
                    })
                    ->get();

                $totalMapelSessionsToday = $schedulesTodayMapel->count();
                $mapelScheduleIds = $schedulesTodayMapel->pluck('id');

                if ($mapelScheduleIds->isNotEmpty()) {
                    $mapelAttendancesToday = SubjectAttendance::whereIn('schedule_id', $mapelScheduleIds)
                        ->whereIn('student_id', $activeStudentIds)
                        ->whereDate('created_at', $today)
                        ->get();
                    
                    $activeMapelSessionsToday = $mapelAttendancesToday->pluck('schedule_id')->unique()->count();
                    $mapelHadirCount = $mapelAttendancesToday->where('status', 'hadir')->count();
                    $mapelBolosCount = $mapelAttendancesToday->where('status', 'bolos')->count();
                }
            } catch (\Throwable $e) {
                $debugErrors['subject_attendance'] = $e->getMessage();
                Log::warning('[PrincipalDashboard] Gagal memuat sesi mapel: ' . $e->getMessage());
            }

            // 3. STATISTIK KOKURIKULER & EKSTRAKURIKULER
            $cocurricularProjectsCount = 0;
            $cocurricularSchedulesToday = 0;
            $extracurricularsCount = 0;
            $extraAttendancesThisWeek = 0;

            try {
                $cocurricularProjectsCount = Schema::hasTable('cocurriculars') ? DB::table('cocurriculars')->count() : 0;
                
                if (Schema::hasColumn('schedules', 'schedule_type')) {
                    $cocurricularSchedulesToday = Schedule::where('schedule_type', 'cocurricular')
                        ->where(function ($q) use ($dayOfWeekIso, $dayOfWeek) {
                            $q->where('day_of_week', $dayOfWeekIso)
                              ->orWhere('day_of_week', $dayOfWeek);
                        })
                        ->count();
                }

                $extracurricularsCount = Schema::hasTable('extracurriculars') ? Extracurricular::count() : 0;
                
                if (Schema::hasTable('extracurricular_attendances')) {
                    $extraAttendancesThisWeek = ExtracurricularAttendance::where('attendance_date', '>=', $today->copy()->startOfWeek())
                        ->where('status', 'hadir')
                        ->count();
                }
            } catch (\Throwable $e) {
                $debugErrors['cocurricular_extra'] = $e->getMessage();
            }

            // 4. SUPERVISI JURNAL MENGAJAR GURU
            $totalJournals = 0;
            $verifiedJournals = 0;
            $pendingJournals = 0;
            $activeTeachersWithJournal = 0;
            $totalTeachers = 0;
            $recentPendingJournals = collect();

            try {
                $totalTeachers = Teacher::count();
                if (Schema::hasTable('teaching_journals')) {
                    $totalJournals = TeachingJournal::count();
                    $hasVerifiedCol = Schema::hasColumn('teaching_journals', 'is_verified');

                    if ($hasVerifiedCol) {
                        $verifiedJournals = TeachingJournal::where('is_verified', true)->count();
                        $pendingJournals = TeachingJournal::where('is_verified', false)->count();
                        $recentPendingJournals = TeachingJournal::with(['teacher', 'subject', 'schoolClass'])
                            ->where('is_verified', false)
                            ->orderBy('date', 'desc')
                            ->orderBy('id', 'desc')
                            ->take(6)
                            ->get();
                    } else {
                        $recentPendingJournals = TeachingJournal::with(['teacher', 'subject', 'schoolClass'])
                            ->orderBy('date', 'desc')
                            ->orderBy('id', 'desc')
                            ->take(6)
                            ->get();
                    }
                    $activeTeachersWithJournal = TeachingJournal::distinct('teacher_id')->count('teacher_id');
                }
            } catch (\Throwable $e) {
                $debugErrors['teaching_journals'] = $e->getMessage();
                Log::warning('[PrincipalDashboard] Gagal memuat jurnal mengajar: ' . $e->getMessage());
            }

            // 5. DATA TREN GRAFIK KEHADIRAN SISWA AKTIF (14 Hari Terakhir)
            $chartDates = [];
            $chartPercentages = [];
            $chartHadirCounts = [];
            $chartTotalCounts = [];

            try {
                $startDate = $today->copy()->subDays(13)->startOfDay();
                $endDate = $today->copy()->endOfDay();

                // Ambil semua data kehadiran siswa aktif dalam rentang 14 hari dalam satu kueri efisien
                $rangeAttendances = Attendance::whereBetween('attendance_time', [$startDate, $endDate])
                    ->whereIn('student_id', $activeStudentIds)
                    ->when(session('active_semester_id'), function ($q) {
                        return $q->where('semester_id', session('active_semester_id'));
                    })
                    ->get();

                for ($i = 13; $i >= 0; $i--) {
                    $date = $today->copy()->subDays($i);
                    // Lewati hari Minggu
                    if ($date->isSunday()) continue;

                    $dateStr = $date->format('Y-m-d');
                    
                    $dayAttendances = $rangeAttendances->filter(function ($att) use ($dateStr) {
                        return $att->attendance_time && Carbon::parse($att->attendance_time)->format('Y-m-d') === $dateStr;
                    });

                    $dayHadir = $dayAttendances->whereIn('status', ['hadir', 'present', 'tepat_waktu', 'terlambat', 'late', 'on_time'])->count();
                    
                    $pct = $totalStudents > 0 ? round(($dayHadir / $totalStudents) * 100, 1) : 0;
                    
                    $chartDates[] = $date->translatedFormat('d M');
                    $chartPercentages[] = $pct;
                    $chartHadirCounts[] = $dayHadir;
                    $chartTotalCounts[] = $totalStudents;
                }
            } catch (\Throwable $e) {
                $debugErrors['attendance_trend_chart'] = $e->getMessage();
                Log::warning('[PrincipalDashboard] Gagal memuat tren kehadiran: ' . $e->getMessage());
            }

            // 6. PERFORMA KEHADIRAN PER KELAS (Hari Ini - Hanya Siswa Aktif)
            $classAttendanceBreakdown = [];

            try {
                $classes = SchoolClass::all()->sortBy('name', SORT_NATURAL);
                foreach ($classes as $cls) {
                    $clsStudents = Student::where('school_class_id', $cls->id)
                        ->when(Schema::hasColumn('students', 'status'), function ($q) {
                            return $q->where('status', 'aktif');
                        })
                        ->get();
                    $clsStudentCount = $clsStudents->count();
                    if ($clsStudentCount === 0) continue;

                    $clsStudentIds = $clsStudents->pluck('id');
                    $clsAttendances = $attendancesToday->whereIn('student_id', $clsStudentIds);
                    $clsHadir = $clsAttendances->whereIn('status', ['hadir', 'present', 'tepat_waktu', 'terlambat', 'late', 'on_time'])->count();
                    $clsPct = round(($clsHadir / $clsStudentCount) * 100);

                    $classAttendanceBreakdown[] = [
                        'name' => $cls->name,
                        'total' => $clsStudentCount,
                        'hadir' => $clsHadir,
                        'alpha' => $clsAttendances->where('status', 'alpa')->count(),
                        'percentage' => $clsPct,
                    ];
                }
            } catch (\Throwable $e) {
                $debugErrors['class_breakdown'] = $e->getMessage();
                Log::warning('[PrincipalDashboard] Gagal memuat performa kelas: ' . $e->getMessage());
            }

            $viewData = compact(
                'totalStudents',
                'totalHadir',
                'presentOnTimeCount',
                'presentLateCount',
                'sickCount',
                'permitCount',
                'alphaCount',
                'unmarkedCount',
                'dailyPresencePercentage',
                'activePermitsCount',
                'totalMapelSessionsToday',
                'activeMapelSessionsToday',
                'mapelHadirCount',
                'mapelBolosCount',
                'cocurricularProjectsCount',
                'cocurricularSchedulesToday',
                'extracurricularsCount',
                'extraAttendancesThisWeek',
                'totalJournals',
                'verifiedJournals',
                'pendingJournals',
                'activeTeachersWithJournal',
                'totalTeachers',
                'recentPendingJournals',
                'chartDates',
                'chartPercentages',
                'chartHadirCounts',
                'chartTotalCounts',
                'classAttendanceBreakdown',
                'debugErrors'
            );

            // Render Blade secara eksplisit di dalam try-catch agar error Blade tertangkap
            $renderedHtml = view('principal.dashboard', $viewData)->render();
            return response($renderedHtml);

        } catch (\Throwable $fatalError) {
            Log::error('[PrincipalDashboard Fatal Error] ' . $fatalError->getMessage(), [
                'file' => $fatalError->getFile(),
                'line' => $fatalError->getLine(),
                'trace' => $fatalError->getTraceAsString(),
            ]);

            // Tampilkan rincian error secara transparan dalam format terstruktur
            return response()->json([
                'status' => 'render_error',
                'message' => $fatalError->getMessage(),
                'file' => $fatalError->getFile(),
                'line' => $fatalError->getLine(),
                'trace_snippet' => array_slice(explode("\n", $fatalError->getTraceAsString()), 0, 8),
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
}
