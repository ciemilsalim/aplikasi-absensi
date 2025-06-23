<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;

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

        $studentsInClass = Student::where('school_class_id', $class->id)
                           ->orderBy('name')
                           ->get();
        
        $attendancesToday = Attendance::whereIn('student_id', $studentsInClass->pluck('id'))
                                      ->whereDate('attendance_time', $today)
                                      ->get();

        // **PERHITUNGAN STATISTIK BARU**
        $totalStudents = $studentsInClass->count();
        $onTimeCount = $attendancesToday->where('status', 'tepat_waktu')->count();
        $lateCount = $attendancesToday->where('status', 'terlambat')->count();
        $sickCount = $attendancesToday->where('status', 'sakit')->count();
        $permitCount = $attendancesToday->where('status', 'izin')->count();
        $alphaCount = $attendancesToday->where('status', 'alpa')->count();

        // Siswa yang belum memiliki catatan kehadiran sama sekali
        $noRecordCount = $totalStudents - $attendancesToday->count();
        
        // Kirim semua data statistik ke view
        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'class' => $class,
            'studentsInClass' => $studentsInClass,
            'attendancesToday' => $attendancesToday->keyBy('student_id'),
            'onTimeCount' => $onTimeCount,
            'lateCount' => $lateCount,
            'sickCount' => $sickCount,
            'permitCount' => $permitCount,
            'alphaCount' => $alphaCount,
            'noRecordCount' => $noRecordCount,
        ]);
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
}
