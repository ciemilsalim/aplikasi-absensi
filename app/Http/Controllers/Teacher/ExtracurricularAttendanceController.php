<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Extracurricular;
use App\Models\Semester;
use App\Services\Teacher\ExtracurricularAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExtracurricularAttendanceController extends Controller
{
    public function __construct(
        protected ExtracurricularAttendanceService $service
    ) {}

    /**
     * Menampilkan daftar kegiatan ekstrakurikuler yang dibina oleh guru.
     */
    public function index()
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(403, 'Akses ditolak.');
        }

        $data = $this->service->getIndexData($teacher);

        return view('teacher.extracurricular_attendance.index', $data);
    }

    /**
     * Menampilkan form input absensi manual (checklist).
     */
    public function create(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            return redirect()->route('teacher.dashboard', ['view' => 'pembina_ekskul'])
                ->with('error', 'Anda bukan pembina ekstrakurikuler ini.');
        }

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if (!$activeYear || !$activeSemester) {
            return back()->with('error', 'Tahun Ajaran atau Semester aktif belum ditentukan oleh Admin.');
        }

        $data = $this->service->getCreateFormData($extracurricular, $teacher, $request->input('date'));

        return view('teacher.extracurricular_attendance.create', $data);
    }

    /**
     * Menyimpan data presensi dari form checklist manual.
     */
    public function store(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.status' => 'required|in:hadir,sakit,izin,alpa',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if (!$activeYear || !$activeSemester) {
            return back()->with('error', 'Gagal menyimpan: Tahun Ajaran atau Semester aktif belum ditentukan oleh Admin.');
        }

        $this->service->storeManualAttendance(
            $extracurricular,
            $request->attendance_date,
            $request->attendances,
            $activeYear,
            $activeSemester
        );

        return redirect()->route('teacher.dashboard', ['view' => 'pembina_ekskul'])
            ->with('success', 'Presensi ekstrakurikuler ' . $extracurricular->name . ' tanggal ' . Carbon::parse($request->attendance_date)->translatedFormat('d F Y') . ' berhasil disimpan.');
    }

    /**
     * Menampilkan antarmuka Scanner Kamera (QR Code & Face Recognition) untuk ekskul.
     */
    public function showScanner(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            return redirect()->route('teacher.dashboard', ['view' => 'pembina_ekskul'])
                ->with('error', 'Anda bukan pembina ekstrakurikuler ini.');
        }

        $data = $this->service->getScannerData($extracurricular, $teacher, $request->query('date'));

        return view('teacher.extracurricular_attendance.scanner', $data);
    }

    /**
     * Menyimpan rekaman presensi via Scan QR Code atau Face Recognition.
     */
    public function storeScan(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $result = $this->service->recordScanAttendance(
            $extracurricular,
            $request->student_unique_id,
            $request->input('date')
        );

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * Menandai status kehadiran secara manual cepat dari modal scanner.
     */
    public function markManual(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:hadir,sakit,izin,alpa,hapus',
            'date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $result = $this->service->markManualAttendance(
            $extracurricular,
            (int)$request->student_id,
            $request->status,
            $request->date,
            $request->notes
        );

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * Menampilkan halaman preview laporan dan matriks rekap kehadiran ekstrakurikuler.
     */
    public function report(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            abort(403, 'Akses ditolak.');
        }

        $data = $this->service->getReportData($extracurricular, $teacher, $request->start_date, $request->end_date);

        return view('teacher.extracurricular_attendance.report', $data);
    }

    /**
     * Menampilkan dokumen cetak PDF resmi ber-kop surat sekolah untuk rekap ekskul.
     */
    public function print(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->service->checkCoachAccess($extracurricular, $teacher)) {
            abort(403, 'Akses ditolak.');
        }

        $data = $this->service->getReportData($extracurricular, $teacher, $request->start_date, $request->end_date);

        return view('teacher.extracurricular_attendance.print', $data);
    }
}
