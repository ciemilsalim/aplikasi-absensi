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
// Impor class yang dibutuhkan untuk export
use App\Exports\AttendanceReportExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    // ... (method index, getHomeroomData, getSubjectTeacherData, updateAttendance tetap sama) ...
    
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
     * Memperbarui atau membuat data absensi siswa.
     */
    public function updateAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|string|in:tepat_waktu,terlambat,sakit,izin,alpa,hapus',
        ]);

        $teacher = Auth::user()->teacher;
        $student = Student::find($request->student_id);

        // Otorisasi: Pastikan guru yang mengubah adalah wali kelas dari siswa tersebut
        if (!$teacher->homeroomClass || $teacher->homeroomClass->id !== $student->school_class_id) {
            return back()->with('error', 'Anda tidak berwenang mengubah absensi siswa ini.');
        }

        $status = $request->input('status');
        $date = Carbon::parse($request->input('date'))->startOfDay();

        // Cari record yang mungkin sudah ada untuk siswa pada tanggal tersebut
        $attendance = Attendance::where('student_id', $request->student_id)
                                ->whereDate('attendance_time', $date)
                                ->first();

        // Kasus 1: Hapus data absensi
        if ($status === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return back()->with('success', 'Riwayat absensi berhasil dihapus.');
            }
            // Jika tidak ada data, tidak ada yang perlu dihapus.
            return back()->with('success', 'Tidak ada perubahan dilakukan.');
        }

        // Kasus 2: Update atau Buat data absensi baru
        if ($attendance) {
            // Jika data sudah ada, perbarui statusnya
            $attendance->status = $status;
            $attendance->save();
        } else {
            // Jika data belum ada, buat record baru
            Attendance::create([
                'student_id' => $request->student_id,
                'status' => $status,
                'attendance_time' => $date, // Simpan tanggal yang dipilih dari modal
            ]);
        }

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

        // Filter untuk mengecualikan akhir pekan (Sabtu & Minggu) agar konsisten dengan halaman cetak
        $period = collect($period)->filter(function ($date) {
            return !$date->isWeekend();
        });

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

    /**
     * METHOD BARU: Menyiapkan data untuk halaman cetak.
     */
    public function printAttendance(Request $request)
    {
        $teacher = Auth::user()->teacher;
        
        if (!$teacher || !$teacher->homeroomClass) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak memiliki kelas untuk dicetak.');
        }

        // Mengambil data pengaturan sekolah
        $settings = Setting::all()->pluck('value', 'key');
        
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
        // Filter untuk mengecualikan akhir pekan (Sabtu & Minggu)
        $workdays = collect($period)->filter(function ($date) {
            return !$date->isWeekend();
        });


        // Mengirim data ke view khusus untuk print
        // CARA PENULISAN DIPERBAIKI: Menggunakan array asosiatif standar, bukan compact()
        return view('teacher.print.attendance-report', [
            'settings' => $settings,
            'class' => $class,
            'students' => $students,
            'attendances' => $attendances,
            'period' => $workdays, // Mengirim data hari kerja yang sudah difilter
            'selectedDate' => $selectedDate
        ]);
    }

    /**
     * Menangani permintaan ekspor ke Excel.
     */
    public function exportAttendanceExcel(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak memiliki kelas untuk diekspor.');
        }

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $selectedDate = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
        
        $fileName = 'Laporan Kehadiran ' . $teacher->homeroomClass->name . ' - ' . $selectedDate->translatedFormat('F Y') . '.xlsx';
        
        return Excel::download(new AttendanceReportExport($selectedDate), $fileName);
    }

    // Fungsi updateNote
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

