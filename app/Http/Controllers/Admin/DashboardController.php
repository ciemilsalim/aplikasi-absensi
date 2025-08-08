<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student; // Impor model Student
use App\Models\StudentPermit; // Impor model StudentPermit
use Carbon\Carbon;

class DashboardController extends Controller
{
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

        $attendancesQuery = Attendance::with(['student.schoolClass'])
                                      ->whereDate('attendance_time', $selectedDate);

        // --- STATISTIK ---
        $allAttendancesToday = (clone $attendancesQuery)->get();
        
        $totalAllStudents = Student::count();
        $totalPresent = $allAttendancesToday->whereIn('status', ['tepat_waktu', 'terlambat'])->count();
        $totalAbsent = $totalAllStudents - $allAttendancesToday->count();
        
        $overallAttendancePercentage = ($totalAllStudents > 0) ? round(($totalPresent / $totalAllStudents) * 100) : 0;
        $overallAbsentPercentage = ($totalAllStudents > 0) ? round(($totalAbsent / $totalAllStudents) * 100) : 0;
        
        $totalOnTime = $allAttendancesToday->where('status', 'tepat_waktu')->count();
        $totalLate = $allAttendancesToday->where('status', 'terlambat')->count();
        $totalEffectivelyAttended = $totalOnTime + $totalLate;
        $overallOnTimePercentage = ($totalEffectivelyAttended > 0) ? round(($totalOnTime / $totalEffectivelyAttended) * 100) : 0;
        $overallLatenessPercentage = ($totalEffectivelyAttended > 0) ? round(($totalLate / $totalEffectivelyAttended) * 100) : 0;
        
        $totalIzin = $allAttendancesToday->where('status', 'izin')->count();
        $totalSakit = $allAttendancesToday->where('status', 'sakit')->count();

        $allClassesWithStudents = SchoolClass::withCount('students')->get();
        $attendancesByClass = $allAttendancesToday->whereIn('status', ['tepat_waktu', 'terlambat'])->groupBy('student.school_class_id');
        $classAttendanceStats = $allClassesWithStudents->map(function ($class) use ($attendancesByClass) {
            $totalStudentsInClass = $class->students_count;
            $attendedCount = isset($attendancesByClass[$class->id]) ? $attendancesByClass[$class->id]->count() : 0;
            $percentage = ($totalStudentsInClass > 0) ? round(($attendedCount / $totalStudentsInClass) * 100) : 0;
            return (object)['name' => $class->name, 'percentage' => $percentage, 'ratio' => "{$attendedCount} / {$totalStudentsInClass} Siswa Hadir"];
        });

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
        
        // Mengambil data siswa yang sedang izin keluar
        $studentsOnPermit = StudentPermit::with(['student.schoolClass'])
            ->whereDate('time_out', $selectedDate)
            ->whereNull('time_in')
            ->get();

        // BARU: Mengambil data siswa yang belum absen pulang
        $studentsNotCheckedOut = Attendance::with(['student.schoolClass'])
            ->whereDate('attendance_time', $selectedDate)
            ->whereNotNull('attendance_time')
            ->whereNull('checkout_time')
            ->whereNotIn('status', ['izin', 'sakit', 'alpa', 'izin_keluar'])
            ->get();
        
        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'classes' => $classes,
            'overallAttendancePercentage' => $overallAttendancePercentage,
            'overallAbsentPercentage' => $overallAbsentPercentage,
            'overallOnTimePercentage' => $overallOnTimePercentage,
            'overallLatenessPercentage' => $overallLatenessPercentage,
            'totalIzin' => $totalIzin,
            'totalSakit' => $totalSakit,
            'classAttendanceStats' => $classAttendanceStats,
            'studentsOnPermit' => $studentsOnPermit,
            'studentsNotCheckedOut' => $studentsNotCheckedOut, // Kirim data baru ke view
        ]);
    }
}
