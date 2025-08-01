<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    /**
     * Menampilkan dasbor untuk guru (wali kelas).
     */
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

        // --- Perhitungan Statistik Harian ---
        $totalStudents = $studentsInClass->count();
        $onTimeCount = $attendancesToday->where('status', 'tepat_waktu')->count();
        $lateCount = $attendancesToday->where('status', 'terlambat')->count();
        $sickCount = $attendancesToday->where('status', 'sakit')->count();
        $permitCount = $attendancesToday->where('status', 'izin')->count();
        $alphaCount = $attendancesToday->where('status', 'alpa')->count();
        $noRecordCount = $totalStudents - $attendancesToday->count();
        
        // --- PERHITUNGAN DATA UNTUK GRAFIK MINGGUAN (BARU) ---
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

        // --- LOGIKA BARU: Mengambil Siswa yang Perlu Perhatian ---
        $studentsForAttention = Student::whereIn('id', $studentIds)
            ->withCount([
                'attendances as late_count' => function ($query) use ($thirtyDaysAgo) {
                    $query->where('status', 'terlambat')->where('attendance_time', '>=', $thirtyDaysAgo);
                },
                'attendances as alpha_count' => function ($query) use ($thirtyDaysAgo) {
                    $query->where('status', 'alpa')->where('attendance_time', '>=', $thirtyDaysAgo);
                }
            ])
            ->having('late_count', '>', 2) // Contoh: lebih dari 2x terlambat
            ->orHaving('alpha_count', '>', 1) // Contoh: lebih dari 1x alpa
            ->orderByDesc('late_count')
            ->orderByDesc('alpha_count')
            ->take(5) // Ambil 5 siswa teratas
            ->get();

        return view('teacher.dashboard', compact(
            'teacher', 'class', 'studentsInClass', 'attendancesToday',
            'onTimeCount', 'lateCount', 'sickCount', 'permitCount', 'alphaCount', 'noRecordCount',
            'studentsForAttention', // Kirim data baru ke view
            'chartLabels', 'chartData' // Kirim data grafik ke view
        ));
    }

    /**
     * Menandai status kehadiran siswa (Izin, Sakit, Alpa) oleh wali kelas.
     */
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

    /**
     * [METODE BARU] Menampilkan halaman riwayat kehadiran dengan filter.
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

        // Validasi dan tentukan rentang tanggal
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

        // Ambil data kehadiran
        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        // Buat periode tanggal untuk header tabel
        $period = CarbonPeriod::create($startDate, $endDate);

        return view('teacher.attendance-history', compact(
            'teacher', 
            'class', 
            'students', 
            'attendances', 
            'period', 
            'startDate', 
            'endDate'
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

        // Pastikan guru adalah wali kelas dari siswa yang bersangkutan
        if (!$teacher || $teacher->homeroomClass?->id !== $student->school_class_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk mengubah data siswa ini.');
        }

        $attendanceDate = Carbon::parse($request->date)->startOfDay();

        // Cari data kehadiran
        $attendance = Attendance::where('student_id', $student->id)
                                ->whereDate('attendance_time', $attendanceDate)
                                ->first();
        
        // Jika user memilih "hapus"
        if ($request->status === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return redirect()->back()->with('success', 'Data kehadiran berhasil dihapus.');
            }
            return redirect()->back(); // Tidak ada yang perlu dihapus
        }

        // Gunakan updateOrCreate untuk memperbarui atau membuat data baru
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
}
