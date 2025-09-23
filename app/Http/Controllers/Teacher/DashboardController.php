<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\StudentPermit;
use App\Models\Schedule;
use App\Models\SubjectAttendance;
use App\Models\TeachingAssignment;
use App\Models\TeacherNote;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan dasbor guru berdasarkan peran yang dimiliki.
     */
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            abort(403, 'Akses ditolak. Anda bukan seorang guru.');
        }

        $viewData = ['teacher' => $teacher];

        $isHomeroomTeacher = $teacher->homeroomClass()->exists();
        $isSubjectTeacher = $teacher->teachingAssignments()->exists();

        if (!$isHomeroomTeacher && !$isSubjectTeacher) {
            return view('teacher.dashboard-no-role', $viewData);
        }

        $defaultView = $isHomeroomTeacher ? 'wali_kelas' : 'guru_mapel';
        $currentView = $request->input('view', $defaultView);

        $viewData['isHomeroomTeacher'] = $isHomeroomTeacher;
        $viewData['isSubjectTeacher'] = $isSubjectTeacher;
        $viewData['currentView'] = $currentView;

        if ($currentView === 'wali_kelas' && $isHomeroomTeacher) {
            $viewData = array_merge($viewData, $this->getHomeroomData($teacher));
        }

        if ($currentView === 'guru_mapel' && $isSubjectTeacher) {
            $viewData = array_merge($viewData, $this->getSubjectTeacherData($teacher));
        }
        
        if (!isset($viewData['schedulesToday'])) {
            $viewData['schedulesToday'] = collect();
        }
        if (!isset($viewData['chartLabels'])) {
            $viewData['chartLabels'] = [];
        }
        if (!isset($viewData['classPerformanceData'])) {
            $viewData['classPerformanceData'] = [];
        }


        return view('teacher.dashboard', $viewData);
    }
    
    private function getHomeroomData($teacher)
    {
        $class = $teacher->homeroomClass;
        $today = Carbon::today();
        $thirtyDaysAgo = now()->subDays(30);

        $studentsInClass = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentIds = $studentsInClass->pluck('id');
        
        $attendancesToday = Attendance::whereIn('student_id', $studentIds)
                                      ->whereDate('attendance_time', $today)
                                      ->get()
                                      ->keyBy('student_id');

        $totalStudents = $studentsInClass->count();
        
        $studentsOnPermit = StudentPermit::with('student')
            ->whereIn('student_id', $studentIds)
            ->whereDate('time_out', $today)
            ->whereNull('time_in')
            ->get();

        $studentsNotCheckedOut = Attendance::with('student')
            ->whereIn('student_id', $studentIds)
            ->whereDate('attendance_time', $today)
            ->whereNotNull('attendance_time')
            ->whereNull('checkout_time')
            ->whereNotIn('status', ['izin', 'sakit', 'alpa', 'izin_keluar'])
            ->get();

        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $period = CarbonPeriod::create($startDate, $endDate);
        
        $weeklyAttendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->get()
            ->groupBy(fn($date) => Carbon::parse($date->attendance_time)->format('Y-m-d'));

        $chartLabels = [];
        $chartData = [];
        foreach ($period as $date) {
            $chartLabels[] = $date->translatedFormat('D, d M');
            $dateString = $date->format('Y-m-d');
            $attendedCount = $weeklyAttendances->has($dateString) ? $weeklyAttendances[$dateString]->count() : 0;
            $percentage = ($totalStudents > 0) ? round(($attendedCount / $totalStudents) * 100) : 0;
            $chartData[] = $percentage;
        }

        $studentsForAttention = Student::whereIn('id', $studentIds)
            ->withCount([
                'attendances as late_count' => fn ($query) => $query->where('status', 'terlambat')->where('attendance_time', '>=', $thirtyDaysAgo),
                'attendances as alpha_count' => fn ($query) => $query->where('status', 'alpa')->where('attendance_time', '>=', $thirtyDaysAgo)
            ])
            ->having('late_count', '>', 2)
            ->orHaving('alpha_count', '>', 1)
            ->orderByDesc('late_count')
            ->orderByDesc('alpha_count')
            ->take(5)
            ->get();

        return [
            'class' => $class,
            'studentsInClass' => $studentsInClass,
            'attendancesToday' => $attendancesToday,
            'onTimeCount' => $attendancesToday->where('status', 'tepat_waktu')->count(),
            'lateCount' => $attendancesToday->where('status', 'terlambat')->count(),
            'sickCount' => $attendancesToday->where('status', 'sakit')->count(),
            'permitCount' => $attendancesToday->where('status', 'izin')->count(),
            'alphaCount' => $attendancesToday->where('status', 'alpa')->count(),
            'noRecordCount' => $totalStudents - $attendancesToday->count(),
            'studentsForAttentionWali' => $studentsForAttention,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'studentsOnPermit' => $studentsOnPermit,
            'studentsNotCheckedOut' => $studentsNotCheckedOut
        ];
    }

    private function getSubjectTeacherData($teacher)
    {
        $now = now();
        $dayOfWeekNumber = $now->dayOfWeek;

        $schedulesToday = Schedule::with([
                'teachingAssignment.schoolClass', 
                'teachingAssignment.subject'
            ])
            ->where('day_of_week', $dayOfWeekNumber)
            ->whereHas('teachingAssignment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->orderBy('start_time', 'asc')
            ->get();

        $currentMonth = $now->month;
        if ($currentMonth >= 7 && $currentMonth <= 12) {
            $semesterStart = $now->copy()->setMonth(7)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(12)->endOfMonth();
        } else {
            $semesterStart = $now->copy()->setMonth(1)->startOfMonth();
            $semesterEnd = $now->copy()->setMonth(6)->endOfMonth();
        }

        $studentsForAttention = SubjectAttendance::where('teacher_id', $teacher->id)
            ->whereIn('status', ['alpa', 'bolos'])
            ->whereBetween('created_at', [$semesterStart, $semesterEnd])
            ->with('student.schoolClass')
            ->select('student_id', 
                DB::raw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as alpa_count'),
                DB::raw('SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) as bolos_count')
            )
            ->groupBy('student_id')
            ->havingRaw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) + SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) > 0')
            ->orderByRaw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) + SUM(CASE WHEN status = "bolos" THEN 1 ELSE 0 END) DESC')
            ->take(5)
            ->get();
            
        $lastAttendanceSummary = null;
        $lastAttendanceRecord = SubjectAttendance::where('teacher_id', $teacher->id)
            ->latest() 
            ->first();

        if ($lastAttendanceRecord) {
            $attendances = SubjectAttendance::where('schedule_id', $lastAttendanceRecord->schedule_id)
                ->whereDate('created_at', $lastAttendanceRecord->created_at->toDateString())
                ->get();

            $summary = $attendances->countBy('status');
            $lastAttendanceSummary = [
                'schedule' => $lastAttendanceRecord->schedule,
                'hadir' => $summary->get('hadir', 0),
                'sakit' => $summary->get('sakit', 0),
                'izin' => $summary->get('izin', 0),
                'alpa' => $summary->get('alpa', 0),
                'bolos' => $summary->get('bolos', 0),
            ];
        }
        
        $classPerformanceData = [];
        $thirtyDaysAgo = now()->subDays(30);
        $assignments = TeachingAssignment::where('teacher_id', $teacher->id)
            ->with('schoolClass', 'subject')
            ->get();

        foreach ($assignments as $assignment) {
            $scheduleIds = Schedule::where('teaching_assignment_id', $assignment->id)->pluck('id');
            if ($scheduleIds->isEmpty()) continue;
            $totalSessions = SubjectAttendance::whereIn('schedule_id', $scheduleIds)->where('created_at', '>=', $thirtyDaysAgo)->distinct(DB::raw('DATE(created_at)'))->count();
            $totalHadir = SubjectAttendance::whereIn('schedule_id', $scheduleIds)->where('status', 'hadir')->where('created_at', '>=', $thirtyDaysAgo)->count();
            $totalStudentsInClass = Student::where('school_class_id', $assignment->school_class_id)->count();
            $potentialAttendance = $totalStudentsInClass * $totalSessions;
            $percentage = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100) : 0;
            $classPerformanceData[] = [
                'label' => $assignment->schoolClass->name . ' - ' . $assignment->subject->name,
                'percentage' => $percentage,
            ];
        }

        $teacherNote = TeacherNote::firstOrCreate(['teacher_id' => $teacher->id]);

        return [
            'schedulesToday' => $schedulesToday,
            'studentsForAttentionMapel' => $studentsForAttention,
            'lastAttendanceSummary' => $lastAttendanceSummary,
            'classPerformanceData' => $classPerformanceData,
            'teacherNote' => $teacherNote,
        ];
    }

    /**
     * PERBAIKAN: Mengganti nama fungsi dari markAttendance menjadi updateAttendance
     * agar sesuai dengan definisi di file routes/web.php.
     */
    public function updateAttendance(Request $request)
    {
        $request->validate([
            // Validasi diperbarui untuk menerima id absensi
            'attendance_id' => 'required|exists:attendances,id',
            'status' => 'required|string|in:tepat_waktu,terlambat,sakit,izin,alpa',
        ]);
    
        $attendance = Attendance::findOrFail($request->input('attendance_id'));
        
        // Otorisasi: Pastikan guru yang mengubah adalah wali kelas dari siswa tersebut
        $teacher = Auth::user()->teacher;
        $studentClassId = $attendance->student->school_class_id;
        
        if (!$teacher->homeroomClass || $teacher->homeroomClass->id !== $studentClassId) {
            return back()->with('error', 'Anda tidak berwenang mengubah absensi siswa ini.');
        }

        $attendance->status = $request->input('status');
        // Jika status diubah menjadi hadir/terlambat, pastikan ada jam masuk.
        // Jika tidak, set ke awal hari untuk menandakan data diubah manual.
        if (in_array($attendance->status, ['tepat_waktu', 'terlambat']) && !$attendance->attendance_time) {
            $attendance->attendance_time = $attendance->created_at->startOfDay();
        }
        $attendance->save();

        return back()->with('success', 'Riwayat absensi berhasil diperbarui.');
    }


    /**
     * Menampilkan riwayat absensi.
     */
    public function showAttendanceHistory(Request $request)
    {
        $teacher = Auth::user()->teacher;
        
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $class = $teacher->homeroomClass;
        $students = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $selectedDate = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();

        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $period = CarbonPeriod::create($startDate, $endDate);

        return view('teacher.attendance-history', compact(
            'teacher', 
            'class', 
            'students', 
            'attendances', 
            'period', 
            'startDate', 
            'endDate',
            'selectedDate'
        ));
    }

    // Fungsi updateNote yang mungkin sudah ada
    public function updateNote(Request $request)
    {
        $request->validate(['content' => 'nullable|string']);
        $teacher = Auth::user()->teacher;
        
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
        }

        $note = TeacherNote::updateOrCreate(
            ['teacher_id' => $teacher->id],
            ['content' => $request->input('content', '')]
        );

        return response()->json(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
    }
}
