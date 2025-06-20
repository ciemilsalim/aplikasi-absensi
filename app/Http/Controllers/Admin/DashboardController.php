<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\SchoolClass; // Impor model SchoolClass
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan data kehadiran di dasbor utama, dengan filter tanggal dan pencarian nama.
     */
    public function index(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
            'search' => 'nullable|string|max:255',
            'school_class_id' => 'nullable|integer|exists:school_classes,id', // Validasi untuk filter kelas
        ]);

        $classes = SchoolClass::orderBy('name')->get();
        // 2. Tentukan tanggal yang akan difilter
        $selectedDate = $request->filled('tanggal')
                        ? Carbon::createFromFormat('Y-m-d', $request->tanggal)
                        : Carbon::today();

        // Query dasar untuk mengambil data kehadiran pada tanggal yang dipilih
        $attendancesQuery = Attendance::with(['student.schoolClass'])
                                      ->whereDate('attendance_time', $selectedDate);

        // --- STATISTIK ---
        // Ambil SEMUA data kehadiran hari ini untuk perhitungan statistik
        $allAttendancesToday = (clone $attendancesQuery)->get();
        $totalAttended = $allAttendancesToday->count();

        // 1. Statistik Tepat Waktu Total (BARU)
        $totalOnTime = $allAttendancesToday->where('status', 'tepat_waktu')->count();
        $overallOnTimePercentage = ($totalAttended > 0) ? round(($totalOnTime / $totalAttended) * 100) : 0;

        // 2. Statistik Keterlambatan Total
        $totalLate = $allAttendancesToday->where('status', 'terlambat')->count();
        $overallLatenessPercentage = ($totalAttended > 0) ? round(($totalLate / $totalAttended) * 100) : 0;

        // 3. Statistik Kehadiran per Kelas
        $allClassesWithStudents = SchoolClass::withCount('students')->get();
        $attendancesByClass = $allAttendancesToday->groupBy('student.school_class_id');
        $classAttendanceStats = $allClassesWithStudents->map(function ($class) use ($attendancesByClass) {
            $totalStudents = $class->students_count;
            $attendedCount = isset($attendancesByClass[$class->id]) ? $attendancesByClass[$class->id]->count() : 0;
            $percentage = ($totalStudents > 0) ? round(($attendedCount / $totalStudents) * 100) : 0;
            return (object)['name' => $class->name, 'percentage' => $percentage, 'ratio' => "{$attendedCount} / {$totalStudents} Siswa"];
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

        // Kirim semua data ke view
        return view('admin.dashboard', [
            'attendances' => $attendances,
            'selectedDate' => $selectedDate,
            'classes' => $classes,
            'overallOnTimePercentage' => $overallOnTimePercentage, // Data baru
            'overallLatenessPercentage' => $overallLatenessPercentage,
            'classAttendanceStats' => $classAttendanceStats,
        ]);
    }
}
