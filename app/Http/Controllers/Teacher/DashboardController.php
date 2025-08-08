<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\StudentPermit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;
        
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

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
        $onTimeCount = $attendancesToday->where('status', 'tepat_waktu')->count();
        $lateCount = $attendancesToday->where('status', 'terlambat')->count();
        $sickCount = $attendancesToday->where('status', 'sakit')->count();
        $permitCount = $attendancesToday->where('status', 'izin')->count();
        $alphaCount = $attendancesToday->where('status', 'alpa')->count();
        $noRecordCount = $totalStudents - $attendancesToday->count();
        
        $studentsOnPermit = StudentPermit::with('student')
            ->whereIn('student_id', $studentIds)
            ->whereDate('time_out', $today)
            ->whereNull('time_in')
            ->get();

        // BARU: Mengambil data siswa yang belum absen pulang di kelas ini
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
            ->groupBy(function($date) {
                return Carbon::parse($date->attendance_time)->format('Y-m-d');
            });

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
                'attendances as late_count' => function ($query) use ($thirtyDaysAgo) {
                    $query->where('status', 'terlambat')->where('attendance_time', '>=', $thirtyDaysAgo);
                },
                'attendances as alpha_count' => function ($query) use ($thirtyDaysAgo) {
                    $query->where('status', 'alpa')->where('attendance_time', '>=', $thirtyDaysAgo);
                }
            ])
            ->having('late_count', '>', 2)
            ->orHaving('alpha_count', '>', 1)
            ->orderByDesc('late_count')
            ->orderByDesc('alpha_count')
            ->take(5)
            ->get();

        return view('teacher.dashboard', compact(
            'teacher', 'class', 'studentsInClass', 'attendancesToday',
            'onTimeCount', 'lateCount', 'sickCount', 'permitCount', 'alphaCount', 'noRecordCount',
            'studentsForAttention',
            'chartLabels', 'chartData',
            'studentsOnPermit',
            'studentsNotCheckedOut' // Kirim data baru ke view
        ));
    }

    public function markAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:izin,sakit,alpa',
        ]);

        $teacher = Auth::user()->teacher;
        $student = Student::findOrFail($request->student_id);

        if (!$teacher || $teacher->homeroomClass?->id !== $student->school_class_id) {
            return redirect()->back()->with('error', 'Anda tidak berhak mengubah status siswa ini.');
        }

        $today = Carbon::today();
        $existingAttendance = Attendance::where('student_id', $student->id)
                                        ->whereDate('attendance_time', $today)
                                        ->first();

        if ($existingAttendance) {
            return redirect()->back()->with('error', 'Siswa ini sudah memiliki catatan kehadiran hari ini.');
        }
        
        Attendance::create([
            'student_id' => $student->id,
            'attendance_time' => $today->startOfDay(),
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Status kehadiran untuk siswa ' . $student->name . ' berhasil diperbarui.');
    }

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

    public function updateAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:tepat_waktu,terlambat,izin,sakit,alpa,hapus',
        ]);

        $teacher = Auth::user()->teacher;
        $student = Student::findOrFail($request->student_id);

        if (!$teacher || $teacher->homeroomClass?->id !== $student->school_class_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk mengubah data siswa ini.');
        }

        $attendanceDate = Carbon::parse($request->date)->startOfDay();

        $attendance = Attendance::where('student_id', $student->id)
                                ->whereDate('attendance_time', $attendanceDate)
                                ->first();
        
        if ($request->status === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return redirect()->back()->with('success', 'Data kehadiran berhasil dihapus.');
            }
            return redirect()->back();
        }

        Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'attendance_time' => $attendanceDate,
            ],
            [
                'status' => $request->status,
            ]
        );

        return redirect()->back()->with('success', 'Kehadiran untuk ' . $student->name . ' pada tanggal ' . $attendanceDate->format('d/m/Y') . ' berhasil diperbarui.');
    }

    public function printAttendance(Request $request)
    {
        $teacher = Auth::user()->teacher;
        
        if (!$teacher || !$teacher->homeroomClass) {
            return "Anda tidak memiliki kelas wali.";
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
            ->get();

        $attendanceSummary = [];
        foreach ($students as $student) {
            $studentAttendances = $attendances->where('student_id', $student->id);
            $attendanceSummary[$student->id] = [
                'H' => $studentAttendances->where('status', 'tepat_waktu')->count(),
                'S' => $studentAttendances->where('status', 'sakit')->count(),
                'I' => $studentAttendances->where('status', 'izin')->count(),
                'A' => $studentAttendances->where('status', 'alpa')->count(),
                'T' => $studentAttendances->where('status', 'terlambat')->count(),
            ];
        }
        
        $dailyAttendances = $attendances->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $period = CarbonPeriod::create($startDate, $endDate);
        $schoolIdentity = Setting::pluck('value', 'key')->toArray();

        return view('teacher.reports.attendance-print', compact(
            'class', 
            'students', 
            'dailyAttendances', 
            'attendanceSummary',
            'period', 
            'startDate', 
            'endDate',
            'schoolIdentity'
        ));
    }
}
