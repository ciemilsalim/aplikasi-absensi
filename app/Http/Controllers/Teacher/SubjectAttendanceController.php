<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectAttendance;
use App\Models\Attendance; // <-- Tambahkan model Attendance
use Carbon\Carbon;

class SubjectAttendanceController extends Controller
{
    /**
     * Menampilkan halaman pemindai QR untuk absensi mata pelajaran.
     */
    public function showScanner(Schedule $schedule)
    {
        $teacher = Auth::user()->teacher;

        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        $today = Carbon::today();
        
        // Ambil siswa yang sudah diabsen 'hadir' untuk mapel ini
        $attendedStudents = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('status', 'hadir') // Hanya yang hadir
            ->whereDate('created_at', $today)
            ->with('student')
            ->get();

        // --- LOGIKA BARU UNTUK MENGAMBIL SISWA IZIN/SAKIT ---
        $classId = $schedule->teachingAssignment->school_class_id;
        $studentsOnLeave = Attendance::with('student')
            ->whereHas('student', function($q) use ($classId) {
                $q->where('school_class_id', $classId);
            })
            ->whereIn('status', ['sakit', 'izin'])
            ->whereDate('attendance_time', $today)
            ->get();
        // --- AKHIR LOGIKA BARU ---

        return view('teacher.subject_attendance_scanner', compact('schedule', 'attendedStudents', 'studentsOnLeave'));
    }

    /**
     * Menyimpan data absensi mata pelajaran dari hasil pemindaian.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string|exists:students,unique_id',
            'schedule_id' => 'required|integer|exists:schedules,id',
        ]);

        $teacher = Auth::user()->teacher;
        $schedule = Schedule::find($request->schedule_id);
        $student = Student::where('unique_id', $request->student_unique_id)->first();
        $today = Carbon::today();

        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }
        
        if ($student->school_class_id !== $schedule->teachingAssignment->school_class_id) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak terdaftar di kelas ini.'], 422);
        }

        $existingAttendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json(['success' => false, 'message' => 'Siswa sudah diabsen sebelumnya.'], 409);
        }

        $attendance = SubjectAttendance::create([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'hadir',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran ' . $student->name . ' berhasil dicatat.',
            'student' => ['name' => $student->name, 'time' => $attendance->created_at->format('H:i:s')]
        ]);
    }

    /**
     * Menampilkan halaman riwayat absensi mata pelajaran.
     */
    public function showHistory(Request $request)
    {
        $teacher = Auth::user()->teacher;
        
        $selectedDate = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $attendances = SubjectAttendance::with(['student', 'schedule.teachingAssignment.subject', 'schedule.teachingAssignment.schoolClass'])
            ->where('teacher_id', $teacher->id)
            ->whereDate('created_at', $selectedDate)
            ->get()
            ->groupBy('schedule_id'); // Kelompokkan berdasarkan jadwal

        return view('teacher.subject_attendance_history', compact('attendances', 'selectedDate'));
    }
}
