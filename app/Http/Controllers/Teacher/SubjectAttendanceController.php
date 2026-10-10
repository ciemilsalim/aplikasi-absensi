<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\Teacher\SubjectAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectAttendanceController extends Controller
{
    public function __construct(
        protected SubjectAttendanceService $service
    ) {}

    /**
     * Menampilkan halaman pemindai QR & Wajah untuk absensi mata pelajaran / kokurikuler.
     */
    public function showScanner(Request $request, Schedule $schedule)
    {
        $teacher = Auth::user()?->teacher;

        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        if (!$this->service->isTeacherAuthorized($schedule, $teacher)) {
            return redirect()->route('teacher.dashboard')->with('error', 'Anda tidak berhak mengakses sesi presensi ini.');
        }

        $classId = $this->service->getScheduleClassId($schedule);
        if (!$classId) {
            return redirect()->route('teacher.dashboard')->with('error', 'Target kelas untuk jadwal ini tidak valid.');
        }

        $data = $this->service->getScannerData($schedule, $request->input('date'));

        return view('teacher.subject_attendance_scanner', $data);
    }

    /**
     * Menyimpan data absensi dari hasil pemindaian QR / Wajah.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_unique_id' => 'required|string',
            'schedule_id' => 'required|integer|exists:schedules,id',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $schedule = Schedule::with(['teachingAssignment', 'cocurricular.teachers', 'schoolClass'])->find($request->schedule_id);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan.'], 404);
        }

        if (!$this->service->isTeacherAuthorized($schedule, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $result = $this->service->recordScanAttendance($schedule, $teacher, $request->student_unique_id, $request->input('date'));

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * Menampilkan halaman riwayat absensi mata pelajaran & kokurikuler.
     */
    public function showHistory(Request $request)
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        $data = $this->service->getHistoryData($teacher, $request->input('date'));

        return view('teacher.subject_attendance_history', $data);
    }

    /**
     * Menandai status siswa secara manual oleh guru mapel / fasilitator kokurikuler.
     */
    public function markManualAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'schedule_id' => 'required|exists:schedules,id',
            'status' => 'required|in:hadir,sakit,izin,alpa,bolos',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $schedule = Schedule::with(['teachingAssignment', 'cocurricular.teachers', 'schoolClass'])->find($request->schedule_id);
        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan.'], 404);
        }

        if (!$this->service->isTeacherAuthorized($schedule, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $result = $this->service->recordManualAttendance(
            $schedule,
            $teacher,
            (int)$request->student_id,
            $request->status,
            $request->input('date')
        );

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * Menampilkan halaman formulir untuk filter rekap absensi.
     */
    public function showReportForm()
    {
        $user = Auth::user();
        $isExecutive = $user?->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster']);
        $teacher = $user?->teacher;

        if (!$teacher && !$isExecutive) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        $data = $this->service->getReportFormData($user, $teacher);

        return view('teacher.report_form', $data);
    }

    /**
     * Menampilkan halaman preview rekap absensi yang bisa diedit.
     */
    public function showReportPreview(Request $request)
    {
        $activityType = $request->input('activity_type', 'regular');

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'cocurricular_id' => 'required|exists:cocurriculars,id',
            ]);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
            ]);
        }

        $user = Auth::user();
        $teacher = $user?->teacher;

        $data = $this->service->getAggregatedReportData($request->all(), $user, $teacher, false);
        if (isset($data['error'])) {
            return back()->with('error', $data['error']);
        }

        $data['requestInputs'] = $request->only(['start_date', 'end_date', 'school_class_id', 'subject_id', 'cocurricular_id', 'activity_type']);

        return view('teacher.report_preview', $data);
    }

    /**
     * Memperbarui data kehadiran dari halaman preview rekap.
     */
    public function updateReportAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|string',
            'school_class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'cocurricular_id' => 'nullable|exists:cocurriculars,id',
        ]);

        $user = Auth::user();
        $teacher = $user?->teacher;

        $result = $this->service->updateReportAttendance($request->all(), $user, $teacher);

        return back()->with($result['type'], $result['message']);
    }

    /**
     * Menghasilkan dan menampilkan halaman cetak rekap absensi.
     */
    public function printReport(Request $request)
    {
        $activityType = $request->input('activity_type', 'regular');

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'cocurricular_id' => 'required|exists:cocurriculars,id',
            ]);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
            ]);
        }

        $user = Auth::user();
        $teacher = $user?->teacher;
        if (!$teacher) {
            return back()->with('error', 'Data guru tidak ditemukan.');
        }

        $data = $this->service->getAggregatedReportData($request->all(), $user, $teacher, true);
        if (isset($data['error'])) {
            return back()->with('error', $data['error']);
        }

        $data['teacher'] = $teacher;
        $data['requestInputs'] = $request->only(['start_date', 'end_date', 'school_class_id', 'subject_id', 'cocurricular_id', 'activity_type']);

        return view('teacher.report_print', $data);
    }

    /**
     * Menampilkan halaman UI untuk Grafik Analitik Mapel & Kokurikuler.
     */
    public function charts()
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return redirect()->route('teacher.dashboard')->with('error', 'Data guru tidak ditemukan.');
        }

        $data = $this->service->getChartFormData($teacher);

        return view('teacher.subject_charts', $data);
    }

    /**
     * Memproses data dan mengembalikan JSON untuk Chart.js (Mendukung Mapel & Kokurikuler).
     */
    public function chartData(Request $request)
    {
        $activityType = $request->input('activity_type', 'regular');

        if ($activityType === 'cocurricular' || $request->filled('cocurricular_id')) {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'cocurricular_id' => 'required|exists:cocurriculars,id',
                'period' => 'required|in:weekly,monthly'
            ]);
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'period' => 'required|in:weekly,monthly'
            ]);
        }

        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            return response()->json(['success' => false, 'message' => 'Data guru tidak ditemukan.'], 403);
        }

        $result = $this->service->getChartAnalyticsData($request->all(), $teacher);

        return response()->json($result);
    }
}
