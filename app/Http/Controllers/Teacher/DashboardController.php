<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\Student;
use App\Models\TeacherNote;
use App\Services\Teacher\TeacherAttendanceReportService;
use App\Services\Teacher\TeacherRoleContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function __construct(
        protected TeacherRoleContextService $roleContextService,
        protected TeacherAttendanceReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            abort(403, 'Akses ditolak. Anda bukan seorang guru.');
        }

        $context = $this->roleContextService->buildDashboardViewData($teacher, $request->input('view'));
        if (!$context['hasRole']) {
            return view('teacher.dashboard-no-role', $context['viewData']);
        }

        return view('teacher.dashboard', $context['viewData']);
    }

    public function updateAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|string|in:tepat_waktu,terlambat,sakit,izin,alpa,hapus',
        ]);

        $teacher = Auth::user()?->teacher;
        $student = Student::find($request->student_id);

        if (!$teacher || !$student || !$teacher->homeroomClass || $teacher->homeroomClass->id !== $student->school_class_id) {
            return back()->with('error', 'Anda tidak berwenang mengubah absensi siswa ini.');
        }

        $status = $request->input('status');
        $date = Carbon::parse($request->input('date'))->startOfDay();

        $attendance = Attendance::where('student_id', $request->student_id)
            ->whereDate('attendance_time', $date)
            ->first();

        if ($status === 'hapus') {
            if ($attendance) {
                $attendance->delete();
                return back()->with('success', 'Riwayat absensi berhasil dihapus.');
            }
            return back()->with('success', 'Tidak ada perubahan dilakukan.');
        }

        if ($attendance) {
            $attendance->status = $status;
            if (is_null($attendance->attendance_time)) {
                $attendance->attendance_time = $date;
            }
            $attendance->save();
        } else {
            $attendance = Attendance::create([
                'student_id' => $request->student_id,
                'status' => $status,
                'attendance_time' => $date,
            ]);
        }

        if ($status !== 'alpa') {
            foreach ($student->parents as $parent) {
                if ($parent->user_id) {
                    Notification::where('user_id', $parent->user_id)
                        ->where('is_read', false)
                        ->where('title', 'like', '%' . $student->name . '%')
                        ->update(['is_read' => true]);
                }
            }
        }

        return back()->with('success', 'Riwayat absensi berhasil diperbarui.');
    }

    public function showAttendanceHistory(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $viewData = $this->reportService->getAttendanceHistoryData($teacher, $request->input('month'));
        if (!$viewData) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        return view('teacher.attendance-history', $viewData);
    }

    public function printAttendance(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'paper_size' => 'nullable|string|in:a4,folio,f4',
        ]);

        $paperSize = strtolower($request->input('paper_size', 'a4'));
        $viewData = $this->reportService->getPrintMonthlyAttendanceData($teacher, $request->input('month'), $paperSize);
        if (!$viewData) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        return view('teacher.print.attendance-report', $viewData);
    }

    public function printTrimesterAttendance(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $request->validate([
            'trimester' => 'required|in:1,2,3,4',
            'year' => 'nullable|digits:4',
            'paper_size' => 'nullable|string|in:a4,folio,f4',
        ]);

        $trimester = (int)$request->input('trimester', 1);
        $year = (int)$request->input('year', date('Y'));
        $paperSize = strtolower($request->input('paper_size', 'a4'));

        $pdfStream = $this->reportService->streamTrimesterPdf($teacher, $trimester, $year, $paperSize);
        if (!$pdfStream) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        return $pdfStream;
    }

    public function charts()
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return view('teacher.dashboard-no-class', compact('teacher'));
        }

        $class = $teacher->homeroomClass;
        $students = TeacherRoleContextService::getClassStudents($class);

        return view('teacher.reports.charts', compact('class', 'students'));
    }

    public function chartData(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return response()->json(['error' => 'Akses tidak sah'], 403);
        }

        $params = $request->validate([
            'target_type' => 'required|in:class,student',
            'period_type' => 'required|in:month,trimester,semester',
            'year' => 'required|integer',
            'period_value' => 'required|integer',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $result = $this->reportService->getChartData($teacher, $params);
        if (!$result) {
            return response()->json(['error' => 'Akses tidak sah'], 403);
        }

        return response()->json($result);
    }

    public function exportAttendanceExcel(Request $request)
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher || !$teacher->homeroomClass) {
            return back()->with('error', 'Anda bukan wali kelas.');
        }

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $download = $this->reportService->downloadAttendanceExcel($teacher, $request->input('month'));
        if (!$download) {
            return back()->with('error', 'Anda bukan wali kelas.');
        }

        return $download;
    }

    public function updateNote(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string|max:1000',
            'note' => 'nullable|string|max:1000',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => 'Guru tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Guru tidak ditemukan.');
        }

        $content = $request->input('content', $request->input('note'));

        TeacherNote::updateOrCreate(
            ['teacher_id' => $teacher->id],
            ['content' => $content]
        );

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json(['success' => true, 'message' => 'Catatan berhasil disimpan.']);
        }

        return back()->with('success', 'Catatan guru berhasil diperbarui.');
    }

    public function updateStudentPhoto(Request $request, Student $student)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher || !$teacher->homeroomClass || $teacher->homeroomClass->id !== $student->school_class_id) {
            return back()->with('error', 'Anda tidak berhak mengubah data siswa ini.');
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            $path = $request->file('photo')->store('students', 'public');
            $student->update([
                'photo' => $path,
                'face_descriptor' => null,
            ]);

            return back()->with('success', 'Foto ' . $student->name . ' berhasil diperbarui.');
        }

        return back()->with('error', 'Gagal mengunggah foto.');
    }
}
