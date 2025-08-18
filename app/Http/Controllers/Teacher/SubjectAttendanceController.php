<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectAttendance;
use App\Models\Attendance;
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
        $classId = $schedule->teachingAssignment->school_class_id;
        
        // 1. Ambil SEMUA catatan absensi untuk JADWAL INI pada HARI INI
        $subjectAttendancesToday = SubjectAttendance::where('schedule_id', $schedule->id)
            ->whereDate('created_at', $today)
            ->with('student')
            ->get();

        // 2. Pisahkan siswa berdasarkan status mereka untuk mata pelajaran ini
        $attendedStudents = $subjectAttendancesToday->where('status', 'hadir');
        $studentsOnLeave = $subjectAttendancesToday->whereIn('status', ['sakit', 'izin', 'bolos', 'alpa']);
        
        // 3. Ambil semua siswa di kelas
        $allStudentIdsInClass = Student::where('school_class_id', $classId)->pluck('id');
        
        // 4. Ambil ID siswa yang sudah punya catatan di absensi mapel ini
        $studentIdsWithRecord = $subjectAttendancesToday->pluck('student_id');

        // 5. Siswa tanpa kabar adalah siswa di kelas yang ID-nya tidak ada di daftar yang sudah punya catatan
        $studentsWithoutNotice = Student::whereIn('id', $allStudentIdsInClass)
            ->whereNotIn('id', $studentIdsWithRecord)
            ->orderBy('name', 'asc')
            ->get();

        return view('teacher.subject_attendance_scanner', compact('schedule', 'attendedStudents', 'studentsOnLeave', 'studentsWithoutNotice'));
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

    /**
     * Menandai status siswa secara manual oleh guru mapel.
     */
    public function markManualAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'schedule_id' => 'required|exists:schedules,id',
            'status' => 'required|in:sakit,izin,alpa,bolos',
        ]);

        $teacher = Auth::user()->teacher;
        $student = Student::find($request->student_id);
        $schedule = Schedule::find($request->schedule_id);
        $today = Carbon::today();

        // Otorisasi: Pastikan guru yang mengajar yang melakukan aksi
        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        // --- PERBAIKAN LOGIKA PENYIMPANAN ---
        // Cari data absensi untuk siswa dan jadwal ini pada hari ini.
        $attendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($attendance) {
            // Jika sudah ada, perbarui statusnya.
            $attendance->update([
                'status' => $request->status,
                'teacher_id' => $teacher->id,
            ]);
        } else {
            // Jika belum ada, buat catatan baru.
            SubjectAttendance::create([
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => $request->status,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status ' . $student->name . ' berhasil diubah menjadi ' . $request->status,
        ]);
    }
}
