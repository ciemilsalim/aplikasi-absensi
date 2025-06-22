<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\SchoolClass;
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
        $totalStudentsWithRecords = $allAttendancesToday->count();

        // Statistik Persentase Izin & Sakit (BARU)
        $totalIzin = $allAttendancesToday->where('status', 'izin')->count();
        $totalSakit = $allAttendancesToday->where('status', 'sakit')->count();
        $overallIzinPercentage = ($totalStudentsWithRecords > 0) ? round(($totalIzin / $totalStudentsWithRecords) * 100) : 0;
        $overallSakitPercentage = ($totalStudentsWithRecords > 0) ? round(($totalSakit / $totalStudentsWithRecords) * 100) : 0;
        
        // Statistik Tepat Waktu & Terlambat
        $totalOnTime = $allAttendancesToday->where('status', 'tepat_waktu')->count();
        $totalLate = $allAttendancesToday->where('status', 'terlambat')->count();
        $totalEffectivelyAttended = $totalOnTime + $totalLate;
        $overallOnTimePercentage = ($totalEffectivelyAttended > 0) ? round(($totalOnTime / $totalEffectivelyAttended) * 100) : 0;
        $overallLatenessPercentage = ($totalEffectivelyAttended > 0) ? round(($totalLate / $totalEffectivelyAttended) * 100) : 0;

        // Statistik Kehadiran per Kelas (Diperbarui untuk tidak menghitung izin/sakit)
        $allClassesWithStudents = SchoolClass::withCount('students')->get();
        $attendancesByClass = $allAttendancesToday
                                ->whereIn('status', ['tepat_waktu', 'terlambat']) // Hanya hitung yang benar-benar hadir
                                ->groupBy('student.school_class_id');
        
        $classAttendanceStats = $allClassesWithStudents->map(function ($class) use ($attendancesByClass) {
            $totalStudents = $class->students_count;
            $attendedCount = isset($attendancesByClass[$class->id]) ? $attendancesByClass[$class->id]->count() : 0;
            $percentage = ($totalStudents > 0) ? round(($attendedCount / $totalStudents) * 100) : 0;
            return (object)[
                'name' => $class->name,
                'percentage' => $percentage,
                'ratio' => "{$attendedCount} / {$totalStudents} Siswa Hadir",
            ];
        });

        // 4. Tambahkan filter pencarian nama jika ada
        if ($request->filled('search')) {
            $search = $request->search;
            $attendancesQuery->whereHas('student', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

                // 5. Terapkan filter kelas jika ada
        if ($request->filled('school_class_id')) {
            $attendancesQuery->whereHas('student', function ($query) use ($request) {
                $query->where('school_class_id', $request->school_class_id);
            });
        }

        // 5. Ambil data yang sudah difilter dengan paginasi
        $attendances = $attendancesQuery->latest('attendance_time')->paginate(15);

        // Ambil daftar semua kelas untuk filter di view
        // $classes = SchoolClass::all();

         $attendances = (clone $attendancesQuery)
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('name', 'like', '%' . $request->search . '%'));
            })
            ->when($request->filled('school_class_id'), function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('school_class_id', $request->school_class_id));
            })
            ->latest('attendance_time')
            ->paginate(15);
        
        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'classes' => $classes,
            'overallOnTimePercentage' => $overallOnTimePercentage,
            'overallLatenessPercentage' => $overallLatenessPercentage,
            'overallIzinPercentage' => $overallIzinPercentage,
            'overallSakitPercentage' => $overallSakitPercentage,
            'classAttendanceStats' => $classAttendanceStats,
        ]);
    }
}


      