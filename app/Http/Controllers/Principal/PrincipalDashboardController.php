<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
use App\Models\AcademicYear;
use App\Models\Semester;
use Carbon\Carbon;

class PrincipalDashboardController extends Controller
{
    /**
     * Tampilkan Executive Overview Dashboard untuk Kepala Sekolah.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat

        // 1. STATISTIK KEHADIRAN HARIAN SEKOLAH HARI INI
        $totalStudents = Student::count();
        $attendancesToday = Attendance::whereDate('attendance_date', $today)->get();
        
        $presentOnTimeCount = $attendancesToday->where('status', 'tepat_waktu')->count();
        $presentLateCount = $attendancesToday->where('status', 'terlambat')->count();
        $totalHadir = $presentOnTimeCount + $presentLateCount;
        $sickCount = $attendancesToday->where('status', 'sakit')->count();
        $permitCount = $attendancesToday->where('status', 'izin')->count();
        $alphaCount = $attendancesToday->where('status', 'alpa')->count();
        $totalMarked = $attendancesToday->count();
        $unmarkedCount = max(0, $totalStudents - $totalMarked);

        $dailyPresencePercentage = $totalStudents > 0 
            ? round(($totalHadir / $totalStudents) * 100, 1) 
            : 0;

        // Siswa sedang izin keluar sekolah (Fail-safe check)
        $activePermitsCount = 0;
        if (Schema::hasTable('student_permits')) {
            try {
                $activePermitsCount = StudentPermit::whereDate('time_out', $today)
                    ->whereNull('time_in')
                    ->count();
            } catch (\Throwable $e) {
                $activePermitsCount = 0;
            }
        }

        // 2. STATISTIK SESI MAPEL HARI INI
        $schedulesTodayMapel = Schedule::where('schedule_type', 'regular')
            ->where('day_of_week', $dayOfWeek)
            ->get();
        $totalMapelSessionsToday = $schedulesTodayMapel->count();

        $mapelScheduleIds = $schedulesTodayMapel->pluck('id');
        $mapelAttendancesToday = SubjectAttendance::whereIn('schedule_id', $mapelScheduleIds)
            ->whereDate('created_at', $today)
            ->get();
        
        $activeMapelSessionsToday = $mapelAttendancesToday->pluck('schedule_id')->unique()->count();
        $mapelHadirCount = $mapelAttendancesToday->where('status', 'hadir')->count();
        $mapelBolosCount = $mapelAttendancesToday->where('status', 'bolos')->count();

        // 3. STATISTIK KOKURIKULER & EKSTRAKURIKULER
        $cocurricularProjectsCount = Schema::hasTable('cocurriculars') ? DB::table('cocurriculars')->count() : 0;
        $cocurricularSchedulesToday = Schedule::where('schedule_type', 'cocurricular')
            ->where('day_of_week', $dayOfWeek)
            ->count();

        $extracurricularsCount = Schema::hasTable('extracurriculars') ? Extracurricular::count() : 0;
        $extraAttendancesThisWeek = 0;
        if (Schema::hasTable('extracurricular_attendances')) {
            try {
                $extraAttendancesThisWeek = ExtracurricularAttendance::where('attendance_date', '>=', $today->copy()->startOfWeek())
                    ->where('status', 'hadir')
                    ->count();
            } catch (\Throwable $e) {
                $extraAttendancesThisWeek = 0;
            }
        }

        // 4. SUPERVISI JURNAL MENGAJAR GURU
        $totalJournals = 0;
        $verifiedJournals = 0;
        $pendingJournals = 0;
        $activeTeachersWithJournal = 0;
        $totalTeachers = Teacher::count();
        $recentPendingJournals = collect();

        if (Schema::hasTable('teaching_journals')) {
            $totalJournals = TeachingJournal::count();
            if (Schema::hasColumn('teaching_journals', 'is_verified')) {
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

        // 5. DATA TREN GRAFIK KEHADIRAN SISWA (14 Hari Terakhir)
        $chartDates = [];
        $chartPercentages = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            if ($date->isSunday()) continue;

            $dateStr = $date->format('Y-m-d');
            $dayAttendance = Attendance::whereDate('attendance_date', $dateStr)->get();
            $dayHadir = $dayAttendance->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
            
            $pct = $totalStudents > 0 ? round(($dayHadir / $totalStudents) * 100) : 0;
            
            $chartDates[] = $date->translatedFormat('d M');
            $chartPercentages[] = $pct;
        }

        // 6. PERFORMA KEHADIRAN PER KELAS (Hari Ini)
        $classes = SchoolClass::all()->sortBy('name', SORT_NATURAL);
        $classAttendanceBreakdown = [];
        foreach ($classes as $cls) {
            $clsStudents = Student::where('school_class_id', $cls->id)->get();
            $clsStudentCount = $clsStudents->count();
            if ($clsStudentCount === 0) continue;

            $clsStudentIds = $clsStudents->pluck('id');
            $clsAttendances = $attendancesToday->whereIn('student_id', $clsStudentIds);
            $clsHadir = $clsAttendances->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
            $clsPct = round(($clsHadir / $clsStudentCount) * 100);

            $classAttendanceBreakdown[] = [
                'name' => $cls->name,
                'total' => $clsStudentCount,
                'hadir' => $clsHadir,
                'alpha' => $clsAttendances->where('status', 'alpa')->count(),
                'percentage' => $clsPct,
            ];
        }

        return view('principal.dashboard', compact(
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
            'classAttendanceBreakdown'
        ));
    }
}
