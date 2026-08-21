<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExtracurricularAttendanceController extends Controller
{
    /**
     * Memeriksa apakah guru login memiliki hak akses sebagai pembina ekskul ini.
     */
    private function checkCoachAccess(Extracurricular $extracurricular, ?Teacher $teacher): bool
    {
        if (!$teacher) {
            return false;
        }
        if ($extracurricular->teacher_id == $teacher->id) {
            return true;
        }
        return $teacher->coachingExtracurriculars()->where('extracurriculars.id', $extracurricular->id)->exists();
    }

    /**
     * Menampilkan daftar kegiatan ekstrakurikuler yang dibina oleh guru.
     */
    public function index()
    {
        $teacher = Auth::user()?->teacher;
        if (!$teacher) {
            abort(403, 'Akses ditolak.');
        }

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        $extracurriculars = $teacher->coachingExtracurriculars()
            ->with(['teachers', 'students.schoolClass'])
            ->withCount('students')
            ->get();
        
        $ekskulIds = $extracurriculars->pluck('id');
        $today = Carbon::today()->format('Y-m-d');

        // Statistik kehadiran hari ini per ekskul
        $todayStats = [];
        foreach ($extracurriculars as $ekskul) {
            $stats = ExtracurricularAttendance::where('extracurricular_id', $ekskul->id)
                ->where('attendance_date', $today)
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $todayStats[$ekskul->id] = [
                'hadir' => $stats->get('hadir', 0),
                'sakit' => $stats->get('sakit', 0),
                'izin' => $stats->get('izin', 0),
                'alpa' => $stats->get('alpa', 0),
                'total' => $stats->sum(),
            ];
        }

        // Hitung KPI Keseluruhan
        $totalMembers = $extracurriculars->sum('students_count');
        $totalSessions = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->distinct('extracurricular_id', 'attendance_date')
            ->count('attendance_date');

        $totalHadir = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->where('status', 'hadir')
            ->count();

        $allAttendancesCount = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)->count();
        $avgAttendanceRate = ($allAttendancesCount > 0) ? round(($totalHadir / $allAttendancesCount) * 100, 1) : 0;

        // Riwayat Sesi Presensi Terbaru (6 sesi terakhir)
        $recentSessions = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->select('extracurricular_id', 'attendance_date', \Illuminate\Support\Facades\DB::raw('count(*) as total_students'))
            ->groupBy('extracurricular_id', 'attendance_date')
            ->orderByDesc('attendance_date')
            ->take(6)
            ->get()
            ->map(function($session) {
                $sessionAttendances = ExtracurricularAttendance::where('extracurricular_id', $session->extracurricular_id)
                    ->where('attendance_date', $session->attendance_date)
                    ->get();
                $session->hadir_count = $sessionAttendances->where('status', 'hadir')->count();
                $session->sakit_count = $sessionAttendances->where('status', 'sakit')->count();
                $session->izin_count = $sessionAttendances->where('status', 'izin')->count();
                $session->alpa_count = $sessionAttendances->where('status', 'alpa')->count();
                $session->extracurricular = Extracurricular::with('teachers')->withCount('students')->find($session->extracurricular_id);
                return $session;
            });

        return view('teacher.extracurricular_attendance.index', compact(
            'teacher',
            'extracurriculars',
            'todayStats',
            'today',
            'activeYear',
            'activeSemester',
            'totalMembers',
            'totalSessions',
            'avgAttendanceRate',
            'recentSessions'
        ));
    }

    /**
     * Menampilkan form input absensi manual (checklist).
     */
    public function create(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
            return redirect()->route('teacher.dashboard', ['view' => 'pembina_ekskul'])
                ->with('error', 'Anda bukan pembina ekstrakurikuler ini.');
        }

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if (!$activeYear || !$activeSemester) {
            return back()->with('error', 'Tahun Ajaran atau Semester aktif belum ditentukan oleh Admin.');
        }

        $extracurricular->load(['teachers', 'students.schoolClass']);
        $dateStr = $request->input('date');
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $today = $selectedDate->format('Y-m-d');

        $existingAttendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('attendance_date', $today)
            ->get()
            ->keyBy('student_id');

        return view('teacher.extracurricular_attendance.create', compact(
            'extracurricular', 
            'activeYear', 
            'activeSemester', 
            'existingAttendances', 
            'today',
            'selectedDate',
            'teacher'
        ));
    }

    /**
     * Menyimpan data presensi dari form checklist manual.
     */
    public function store(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
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

        foreach ($request->attendances as $studentId => $data) {
            ExtracurricularAttendance::updateOrCreate(
                [
                    'extracurricular_id' => $extracurricular->id,
                    'student_id' => $studentId,
                    'attendance_date' => $request->attendance_date,
                ],
                [
                    'academic_year_id' => $activeYear->id,
                    'semester_id' => $activeSemester->id,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        return redirect()->route('teacher.dashboard', ['view' => 'pembina_ekskul'])
            ->with('success', 'Presensi ekstrakurikuler ' . $extracurricular->name . ' tanggal ' . Carbon::parse($request->attendance_date)->translatedFormat('d F Y') . ' berhasil disimpan.');
    }

    /**
     * Menampilkan antarmuka Scanner Kamera (QR Code & Face Recognition) untuk ekskul.
     */
    public function showScanner(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
            return redirect()->route('teacher.dashboard', ['view' => 'pembina_ekskul'])
                ->with('error', 'Anda bukan pembina ekstrakurikuler ini.');
        }

        $dateStr = $request->query('date');
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $todayStr = $selectedDate->format('Y-m-d');

        $extracurricular->load(['teachers', 'students.schoolClass']);
        $students = $extracurricular->students->sortBy('name');

        $attendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('attendance_date', $todayStr)
            ->get()
            ->keyBy('student_id');

        // Grouping siswa
        $studentsHadir = [];
        $studentsIzin = [];
        $studentsBelumAbsen = [];

        foreach ($students as $student) {
            $att = $attendances->get($student->id);
            if ($att) {
                if ($att->status === 'hadir') {
                    $studentsHadir[] = [
                        'student' => $student,
                        'attendance' => $att,
                    ];
                } elseif (in_array($att->status, ['sakit', 'izin'])) {
                    $studentsIzin[] = [
                        'student' => $student,
                        'attendance' => $att,
                    ];
                } else {
                    $studentsBelumAbsen[] = [
                        'student' => $student,
                        'attendance' => $att,
                    ];
                }
            } else {
                $studentsBelumAbsen[] = [
                    'student' => $student,
                    'attendance' => null,
                ];
            }
        }

        $studentsWithPhotos = $students->map(function($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'unique_id' => $s->unique_id ?? ('STD-' . $s->id),
                'photo_url' => $s->photo_url,
                'face_descriptor' => $s->face_descriptor ?? null
            ];
        })->values()->toArray();

        return view('teacher.extracurricular_attendance.scanner', compact(
            'extracurricular',
            'selectedDate',
            'students',
            'attendances',
            'studentsHadir',
            'studentsIzin',
            'studentsBelumAbsen',
            'studentsWithPhotos',
            'teacher'
        ));
    }

    /**
     * Menyimpan rekaman presensi via Scan QR Code atau Face Recognition.
     */
    public function storeScan(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $qrData = $request->student_unique_id;
        if (!$qrData) {
            return response()->json(['success' => false, 'message' => 'Kode QR / Identitas siswa tidak diterima.'], 400);
        }

        $parts = explode('-', $qrData, 2);
        if (count($parts) === 2) {
            $student = Student::where('nis', $parts[0])->where('unique_id', $parts[1])->first();
        } else {
            $student = Student::where('unique_id', $qrData)->orWhere('nis', $qrData)->orWhere('id', $qrData)->first();
        }

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak ditemukan atau QR Code tidak valid.'], 404);
        }

        // Pastikan siswa terdaftar di ekskul ini
        if (!$extracurricular->students()->where('students.id', $student->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Siswa ' . $student->name . ' tidak terdaftar sebagai anggota ekstrakurikuler ' . $extracurricular->name . '.'], 422);
        }

        $dateStr = $request->input('date');
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $attendanceDate = $selectedDate->format('Y-m-d');

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        $existing = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('student_id', $student->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        if ($existing && $existing->status === 'hadir') {
            return response()->json([
                'success' => false,
                'message' => 'Siswa ' . $student->name . ' sudah tercatat Hadir hari ini.',
                'already_recorded' => true
            ], 409);
        }

        $attendance = ExtracurricularAttendance::updateOrCreate(
            [
                'extracurricular_id' => $extracurricular->id,
                'student_id' => $student->id,
                'attendance_date' => $attendanceDate,
            ],
            [
                'academic_year_id' => $activeYear?->id,
                'semester_id' => $activeSemester?->id,
                'status' => 'hadir',
                'notes' => 'Presensi via Scanner Kamera'
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran ' . $student->name . ' berhasil dicatat!',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis ?? '-',
                'class_name' => $student->schoolClass?->name ?? '-',
                'status' => 'hadir',
                'time' => now()->format('H:i:s'),
                'photo_url' => $student->photo_url,
            ]
        ]);
    }

    /**
     * Menandai status kehadiran secara manual cepat dari modal scanner.
     */
    public function markManual(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
            return response()->json(['success' => false, 'message' => 'Otorisasi gagal.'], 403);
        }

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:hadir,sakit,izin,alpa,hapus',
            'date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $student = Student::with('schoolClass')->findOrFail($request->student_id);

        if (!$extracurricular->students()->where('students.id', $student->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak terdaftar di ekstrakurikuler ini.'], 422);
        }

        $attendanceDate = Carbon::parse($request->date)->format('Y-m-d');
        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if ($request->status === 'hapus') {
            ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
                ->where('student_id', $student->id)
                ->where('attendance_date', $attendanceDate)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Presensi ' . $student->name . ' berhasil direset (Belum Absen).',
                'student_id' => $student->id,
                'status' => 'belum_absen'
            ]);
        }

        $attendance = ExtracurricularAttendance::updateOrCreate(
            [
                'extracurricular_id' => $extracurricular->id,
                'student_id' => $student->id,
                'attendance_date' => $attendanceDate,
            ],
            [
                'academic_year_id' => $activeYear?->id,
                'semester_id' => $activeSemester?->id,
                'status' => $request->status,
                'notes' => $request->notes
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Status presensi ' . $student->name . ' berhasil diubah menjadi ' . ucfirst($request->status) . '.',
            'student_id' => $student->id,
            'status' => $request->status
        ]);
    }

    /**
     * Menampilkan halaman preview laporan dan matriks rekap kehadiran ekstrakurikuler.
     */
    public function report(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
            abort(403, 'Akses ditolak.');
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $students = $extracurricular->students()->with('schoolClass')->orderBy('name')->get();
        $extracurricular->load('teachers');
        
        $attendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $attendanceData[$attendance->student_id][$attendance->attendance_date] = $attendance->status;
        }

        $dates = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            if ($attendances->contains('attendance_date', $dateStr)) {
                $dates[] = $dateStr;
            }
        }

        // Hitung KPI
        $totalSessions = count($dates);
        $totalMembers = $students->count();
        $totalHadir = $attendances->where('status', 'hadir')->count();
        $totalSakit = $attendances->where('status', 'sakit')->count();
        $totalIzin = $attendances->where('status', 'izin')->count();
        $totalAlpa = $attendances->where('status', 'alpa')->count();

        $potentialAttendance = $totalMembers * $totalSessions;
        $classAvgPercent = ($potentialAttendance > 0) ? round(($totalHadir / $potentialAttendance) * 100, 1) : 0;

        $settings = Setting::whereIn('key', [
            'app_logo', 'school_name', 'school_address', 'school_phone', 'school_email', 
            'school_headmaster_name', 'school_headmaster_nip'
        ])->pluck('value', 'key');

        $schoolIdentity = [
            'logo' => $settings->get('app_logo'),
            'name' => $settings->get('school_name', 'SMP NEGERI 1 BIAU'),
            'address' => $settings->get('school_address', 'Jl. Pendidikan No. 1'),
            'phone' => $settings->get('school_phone'),
            'email' => $settings->get('school_email'),
            'headmaster_name' => $settings->get('school_headmaster_name', '-'),
            'headmaster_nip' => $settings->get('school_headmaster_nip', '-'),
        ];

        $requestInputs = [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ];

        return view('teacher.extracurricular_attendance.report', compact(
            'extracurricular',
            'students',
            'dates',
            'attendanceData',
            'startDate',
            'endDate',
            'schoolIdentity',
            'teacher',
            'totalSessions',
            'totalMembers',
            'totalHadir',
            'totalSakit',
            'totalIzin',
            'totalAlpa',
            'classAvgPercent',
            'requestInputs'
        ));
    }

    /**
     * Menampilkan dokumen cetak PDF resmi ber-kop surat sekolah untuk rekap ekskul.
     */
    public function print(Request $request, Extracurricular $extracurricular)
    {
        $teacher = Auth::user()?->teacher;
        if (!$this->checkCoachAccess($extracurricular, $teacher)) {
            abort(403, 'Akses ditolak.');
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $students = $extracurricular->students()->with('schoolClass')->orderBy('name')->get();
        $extracurricular->load('teachers');
        
        $attendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $attendanceData = [];
        foreach ($attendances as $attendance) {
            $attendanceData[$attendance->student_id][$attendance->attendance_date] = $attendance->status;
        }

        $dates = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            if ($attendances->contains('attendance_date', $dateStr)) {
                $dates[] = $dateStr;
            }
        }

        $settings = Setting::whereIn('key', [
            'app_logo', 'school_name', 'school_address', 'school_phone', 'school_email', 
            'school_headmaster_name', 'school_headmaster_nip'
        ])->pluck('value', 'key');

        $schoolIdentity = [
            'logo' => $settings->get('app_logo'),
            'name' => $settings->get('school_name', 'SMP NEGERI 1 BIAU'),
            'address' => $settings->get('school_address', 'Jl. Pendidikan No. 1'),
            'phone' => $settings->get('school_phone'),
            'email' => $settings->get('school_email'),
            'headmaster_name' => $settings->get('school_headmaster_name', '-'),
            'headmaster_nip' => $settings->get('school_headmaster_nip', '-'),
        ];

        return view('teacher.extracurricular_attendance.print', compact(
            'extracurricular',
            'students',
            'dates',
            'attendanceData',
            'startDate',
            'endDate',
            'schoolIdentity',
            'teacher'
        ));
    }
}
