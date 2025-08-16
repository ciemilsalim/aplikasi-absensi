<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\SubjectAttendance;
use Carbon\Carbon;

class SubjectAttendanceController extends Controller
{
    /**
     * Menampilkan halaman pemindai QR untuk absensi mata pelajaran.
     *
     * @param  \App\Models\Schedule  $schedule
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showScanner(Schedule $schedule)
    {
        $teacher = Auth::user()->teacher;

        // Otorisasi: Pastikan guru yang login adalah guru yang mengajar di jadwal ini
        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        // Ambil daftar siswa yang sudah diabsen untuk jadwal ini hari ini
        $today = Carbon::today();
        $attendedStudents = SubjectAttendance::where('schedule_id', $schedule->id)
            ->whereDate('created_at', $today)
            ->with('student')
            ->get();

        return view('teacher.subject_attendance_scanner', compact('schedule', 'attendedStudents'));
    }

    /**
     * Menyimpan data absensi mata pelajaran dari hasil pemindaian.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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

        // Otorisasi: Pastikan guru yang mengajar yang melakukan scan
        if ($schedule->teachingAssignment->teacher_id !== $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }
        
        // Cek apakah siswa berada di kelas yang benar sesuai jadwal
        if ($student->school_class_id !== $schedule->teachingAssignment->school_class_id) {
            return response()->json([
                'success' => false, 
                'message' => 'Siswa tidak terdaftar di kelas ini.'
            ], 422);
        }

        // Cek duplikasi: Pastikan siswa belum diabsen untuk jadwal ini hari ini
        $existingAttendance = SubjectAttendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false, 
                'message' => 'Siswa sudah diabsen sebelumnya.'
            ], 409);
        }

        // Simpan data absensi
        $attendance = SubjectAttendance::create([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'hadir', // Default status saat scan adalah 'hadir'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran ' . $student->name . ' berhasil dicatat.',
            'student' => [
                'name' => $student->name,
                'time' => $attendance->created_at->format('H:i:s')
            ]
        ]);
    }
}
