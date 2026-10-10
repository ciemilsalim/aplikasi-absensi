<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Services\Attendance\EarlyWarningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(
        protected EarlyWarningService $earlyWarningService
    ) {}

    private function checkEffectiveDaysSet()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        $effectiveDays = \App\Models\Setting::where('key', 'effective_days_' . $currentYear . '_' . $currentMonth)->value('value');
        if ($effectiveDays === null) {
            $effectiveDays = \App\Models\Setting::where('key', 'effective_days_' . $currentMonth)->value('value');
        }
        return !empty($effectiveDays) && $effectiveDays > 0;
    }

    public function index(Request $request)
    {
        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
            'search' => 'nullable|string|max:255',
            'school_class_id' => 'nullable|integer|exists:school_classes,id',
        ]);

        $classes = SchoolClass::orderBy('name')->get();
        $selectedDate = $request->filled('tanggal')
                        ? Carbon::createFromFormat('Y-m-d', $request->tanggal)
                        : Carbon::today();

        // --- FILTER SISWA AKTIF ---
        $activeStudentsQuery = Student::query()
            ->when(Schema::hasColumn('students', 'status'), function ($q) {
                return $q->where('status', 'aktif');
            });

        $activeStudentIds = (clone $activeStudentsQuery)->pluck('id');
        $totalAllStudents = (clone $activeStudentsQuery)->count();

        $attendancesQuery = Attendance::with(['student.schoolClass'])
            ->whereDate('attendance_time', $selectedDate)
            ->whereIn('student_id', $activeStudentIds)
            ->when(session('active_semester_id'), function ($q) {
                return $q->where('semester_id', session('active_semester_id'));
            });

        // --- STATISTIK ---
        $allAttendancesToday = (clone $attendancesQuery)->get();

        $totalRecorded = $allAttendancesToday->count();
        $hasAttendanceData = ($totalRecorded > 0);

        $totalPresent = $allAttendancesToday->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
        $totalOnTime = $allAttendancesToday->where('status', 'tepat_waktu')->count();
        $totalLate = $allAttendancesToday->where('status', 'terlambat')->count();
        $totalIzin = $allAttendancesToday->where('status', 'izin')->count();
        $totalSakit = $allAttendancesToday->where('status', 'sakit')->count();
        $totalAlpa = $allAttendancesToday->where('status', 'alpa')->count();

        // Siswa aktif yang belum tercatat presensi
        $totalUnrecorded = max(0, $totalAllStudents - $totalRecorded);

        // Kalkulasi persentase dengan aman
        $overallAttendancePercentage = ($totalAllStudents > 0 && $hasAttendanceData) ? round(($totalPresent / $totalAllStudents) * 100) : 0;
        $overallAbsentPercentage = ($totalAllStudents > 0 && $hasAttendanceData) ? round(($totalAlpa / $totalAllStudents) * 100) : 0;

        $totalEffectivelyAttended = $totalOnTime + $totalLate;
        $overallOnTimePercentage = ($totalEffectivelyAttended > 0) ? round(($totalOnTime / $totalEffectivelyAttended) * 100) : 0;
        $overallLatenessPercentage = ($totalEffectivelyAttended > 0) ? round(($totalLate / $totalEffectivelyAttended) * 100) : 0;

        // Hitung rombel hanya untuk siswa aktif
        $allClassesWithStudents = SchoolClass::withCount(['students' => function ($q) {
            $q->when(Schema::hasColumn('students', 'status'), function ($sq) {
                return $sq->where('status', 'aktif');
            });
        }])->get();

        $attendancesByClass = $allAttendancesToday->whereIn('status', ['tepat_waktu', 'terlambat'])->groupBy('student.school_class_id');
        $classAttendanceStats = $allClassesWithStudents->map(function ($class) use ($attendancesByClass) {
            $totalStudentsInClass = $class->students_count;
            $attendedCount = isset($attendancesByClass[$class->id]) ? $attendancesByClass[$class->id]->count() : 0;
            $percentage = ($totalStudentsInClass > 0) ? round(($attendedCount / $totalStudentsInClass) * 100) : 0;
            return (object)[
                'name' => $class->name,
                'percentage' => $percentage,
                'ratio' => "{$attendedCount} / {$totalStudentsInClass} Hadir",
                'attended' => $attendedCount,
                'total' => $totalStudentsInClass
            ];
        });

        // Jumlah permohonan izin pending
        $pendingLeaveRequestsCount = 0;
        if (Schema::hasTable('leave_requests')) {
            try {
                $pendingLeaveRequestsCount = LeaveRequest::where('status', 'menunggu')
                    ->orWhere('status', 'pending')
                    ->count();
            } catch (\Throwable $e) {
                $pendingLeaveRequestsCount = 0;
            }
        }

        // --- FILTER TABEL ---
        $attendances = (clone $attendancesQuery)
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('name', 'like', '%' . $request->search . '%'));
            })
            ->when($request->filled('school_class_id'), function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('school_class_id', $request->school_class_id));
            })
            ->latest('attendance_time')
            ->paginate(15);

        // Siswa aktif yang sedang izin keluar
        $studentsOnPermit = StudentPermit::with(['student.schoolClass'])
            ->whereDate('time_out', $selectedDate)
            ->whereIn('student_id', $activeStudentIds)
            ->whereNull('time_in')
            ->get();

        // Siswa aktif yang hadir tapi belum absen pulang
        $studentsNotCheckedOut = Attendance::with(['student.schoolClass'])
            ->whereDate('attendance_time', $selectedDate)
            ->whereIn('student_id', $activeStudentIds)
            ->whereNotNull('attendance_time')
            ->whereNull('checkout_time')
            ->whereNotIn('status', ['izin', 'sakit', 'alpa', 'izin_keluar'])
            ->when(session('active_semester_id'), function ($q) {
                return $q->where('semester_id', session('active_semester_id'));
            })
            ->get();

        // --- EARLY WARNING SYSTEM: SISWA BUTUH PERHATIAN ---
        $attentionClassId = $request->input('school_class_id');
        $attentionStudents = $this->earlyWarningService->getAttentionStudents([
            'class_id' => $attentionClassId,
            'days' => 30,
            'limit' => 15,
        ]);
        $earlyWarningSummary = $this->earlyWarningService->getEarlyWarningSummary($attentionStudents);

        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'classes' => $classes,
            'hasAttendanceData' => $hasAttendanceData,
            'totalAllStudents' => $totalAllStudents,
            'totalRecorded' => $totalRecorded,
            'totalPresent' => $totalPresent,
            'totalOnTime' => $totalOnTime,
            'totalLate' => $totalLate,
            'totalIzin' => $totalIzin,
            'totalSakit' => $totalSakit,
            'totalAlpa' => $totalAlpa,
            'totalUnrecorded' => $totalUnrecorded,
            'overallAttendancePercentage' => $overallAttendancePercentage,
            'overallAbsentPercentage' => $overallAbsentPercentage,
            'overallOnTimePercentage' => $overallOnTimePercentage,
            'overallLatenessPercentage' => $overallLatenessPercentage,
            'classAttendanceStats' => $classAttendanceStats,
            'studentsOnPermit' => $studentsOnPermit,
            'studentsNotCheckedOut' => $studentsNotCheckedOut,
            'pendingLeaveRequestsCount' => $pendingLeaveRequestsCount,
            'isEffectiveDaysSet' => $this->checkEffectiveDaysSet(),
            'attentionStudents' => $attentionStudents,
            'earlyWarningSummary' => $earlyWarningSummary,
        ]);
    }
}
