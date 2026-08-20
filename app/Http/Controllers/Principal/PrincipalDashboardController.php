<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $thirtyDaysAgo = $today->copy()->subDays(30);

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

        // Siswa sedang izin keluar sekolah
        $activePermitsCount = StudentPermit::whereDate('permit_date', $today)
            ->whereNull('checkout_time')
            ->count();

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
        $cocurricularProjectsCount = DB::table('cocurriculars')->count();
        $cocurricularSchedulesToday = Schedule::where('schedule_type', 'cocurricular')
            ->where('day_of_week', $dayOfWeek)
            ->count();

        $extracurricularsCount = Extracurricular::count();
        $extraAttendancesThisWeek = ExtracurricularAttendance::where('attendance_date', '>=', $today->copy()->startOfWeek())
            ->where('status', 'hadir')
            ->count();

        // 4. SUPERVISI JURNAL MENGAJAR GURU
        $totalJournals = TeachingJournal::count();
        $verifiedJournals = TeachingJournal::where('is_verified', true)->count();
        $pendingJournals = TeachingJournal::where('is_verified', false)->count();
        $activeTeachersWithJournal = TeachingJournal::distinct('teacher_id')->count('teacher_id');
        $totalTeachers = Teacher::count();

        // Daftar 6 Jurnal Terbaru yang Menunggu Supervisi
        $recentPendingJournals = TeachingJournal::with(['teacher', 'subject', 'schoolClass'])
            ->where('is_verified', false)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        // 5. DATA TREN GRAFIK KEHADIRAN SISWA (14 Hari Terakhir)
        $chartDates = [];
        $chartPercentages = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            // Lewati hari Minggu
            if ($date->isSunday()) continue;

            $dateStr = $date->format('Y-m-d');
            $dayAttendance = Attendance::whereDate('attendance_date', $dateStr)->get();
            $dayHadir = $dayAttendance->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
            
            $pct = $totalStudents > 0 ? round(($dayHadir / $totalStudents) * 100) : 0;
            
            $chartDates[] = $date->translatedFormat('d M');
            $chartPercentages[] = $pct;
        }

        // 6. PERFORMA KEHADIRAN PER KELAS (Hari Ini)
        $classes = SchoolClass::withCount('students')->get()->sortBy('name', SORT_NATURAL);
        $classAttendanceBreakdown = [];
        foreach ($classes as $cls) {
            $clsStudentCount = $cls->students_count;
            if ($clsStudentCount === 0) continue;

            $clsStudentIds = $cls->students()->pluck('id');
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
