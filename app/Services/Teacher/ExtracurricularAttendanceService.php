<?php

namespace App\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ExtracurricularAttendanceService
{
    /**
     * Memeriksa apakah guru login memiliki hak akses sebagai pembina ekskul ini.
     */
    public function checkCoachAccess(Extracurricular $extracurricular, ?Teacher $teacher): bool
    {
        if (!$teacher) {
            return false;
        }

        if ($extracurricular->teacher_id == $teacher->id) {
            return true;
        }

        return $teacher->coachingExtracurriculars()
            ->where('extracurriculars.id', $extracurricular->id)
            ->exists();
    }

    /**
     * Mengambil data dasbor daftar kegiatan ekstrakurikuler yang dibina guru.
     */
    public function getIndexData(Teacher $teacher): array
    {
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
                ->select('status', DB::raw('count(*) as total'))
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
            ->select('extracurricular_id', 'attendance_date')
            ->distinct()
            ->count();

        $totalHadir = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->where('status', 'hadir')
            ->count();

        $allAttendancesCount = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)->count();
        $avgAttendanceRate = ($allAttendancesCount > 0) ? round(($totalHadir / $allAttendancesCount) * 100, 1) : 0;

        // Riwayat Sesi Presensi Terbaru (6 sesi terakhir)
        $recentSessions = ExtracurricularAttendance::whereIn('extracurricular_id', $ekskulIds)
            ->select('extracurricular_id', 'attendance_date', DB::raw('count(*) as total_students'))
            ->groupBy('extracurricular_id', 'attendance_date')
            ->orderByDesc('attendance_date')
            ->take(6)
            ->get()
            ->map(function ($session) {
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

        return [
            'teacher' => $teacher,
            'extracurriculars' => $extracurriculars,
            'todayStats' => $todayStats,
            'today' => $today,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'totalMembers' => $totalMembers,
            'totalSessions' => $totalSessions,
            'avgAttendanceRate' => $avgAttendanceRate,
            'recentSessions' => $recentSessions,
        ];
    }

    /**
     * Mengambil data form input checklist presensi manual.
     */
    public function getCreateFormData(Extracurricular $extracurricular, Teacher $teacher, ?string $dateStr = null): array
    {
        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        $extracurricular->load(['teachers', 'students.schoolClass']);
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $today = $selectedDate->format('Y-m-d');

        $existingAttendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('attendance_date', $today)
            ->get()
            ->keyBy('student_id');

        return [
            'extracurricular' => $extracurricular,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'existingAttendances' => $existingAttendances,
            'today' => $today,
            'selectedDate' => $selectedDate,
            'teacher' => $teacher,
        ];
    }

    /**
     * Menyimpan data presensi dari form checklist manual.
     */
    public function storeManualAttendance(Extracurricular $extracurricular, string $attendanceDate, array $attendances, ?AcademicYear $activeYear, ?Semester $activeSemester): void
    {
        foreach ($attendances as $studentId => $data) {
            ExtracurricularAttendance::updateOrCreate(
                [
                    'extracurricular_id' => $extracurricular->id,
                    'student_id' => $studentId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'academic_year_id' => $activeYear?->id,
                    'semester_id' => $activeSemester?->id,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }
    }

    /**
     * Mengambil data untuk Scanner Kamera (QR Code & Face Recognition).
     */
    public function getScannerData(Extracurricular $extracurricular, Teacher $teacher, ?string $dateStr = null): array
    {
        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $todayStr = $selectedDate->format('Y-m-d');

        $extracurricular->load(['teachers', 'students.schoolClass']);
        $students = $extracurricular->students->sortBy('name');

        $attendances = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('attendance_date', $todayStr)
            ->get()
            ->keyBy('student_id');

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

        $studentsWithPhotos = $students->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'unique_id' => $s->unique_id ?? ('STD-' . $s->id),
                'photo_url' => $s->photo_url,
                'face_descriptor' => $s->face_descriptor ?? null
            ];
        })->values()->toArray();

        return [
            'extracurricular' => $extracurricular,
            'selectedDate' => $selectedDate,
            'students' => $students,
            'attendances' => $attendances,
            'studentsHadir' => $studentsHadir,
            'studentsIzin' => $studentsIzin,
            'studentsBelumAbsen' => $studentsBelumAbsen,
            'studentsWithPhotos' => $studentsWithPhotos,
            'teacher' => $teacher,
        ];
    }

    /**
     * Menyimpan data presensi via scan QR Code atau Face Recognition.
     */
    public function recordScanAttendance(Extracurricular $extracurricular, ?string $qrData, ?string $dateStr = null): array
    {
        if (!$qrData) {
            return [
                'status' => 400,
                'payload' => ['success' => false, 'message' => 'Kode QR / Identitas siswa tidak diterima.']
            ];
        }

        // 1. Cari exact match unique_id, nis, atau id
        $student = Student::where('unique_id', $qrData)
            ->orWhere('nis', $qrData)
            ->orWhere('id', $qrData)
            ->first();

        // 2. Fallback jika QR formatnya {nis}-{unique_id}
        if (!$student && str_contains($qrData, '-')) {
            $parts = explode('-', $qrData, 2);
            $student = Student::where('nis', $parts[0])->where('unique_id', $parts[1])->first();
        }

        if (!$student) {
            return [
                'status' => 404,
                'payload' => ['success' => false, 'message' => 'Siswa tidak ditemukan atau QR Code tidak valid.']
            ];
        }

        if (in_array(strtolower(trim((string)($student->status ?? ''))), Student::$inactiveStatuses)) {
            return [
                'status' => 422,
                'payload' => [
                    'success' => false,
                    'message' => 'Presensi ditolak. Siswa ' . $student->name . ' berstatus tidak aktif (' . ($student->status ?? 'nonaktif') . ').'
                ]
            ];
        }

        if (!$extracurricular->students()->where('students.id', $student->id)->exists()) {
            return [
                'status' => 422,
                'payload' => [
                    'success' => false,
                    'message' => 'Siswa ' . $student->name . ' tidak terdaftar sebagai anggota ekstrakurikuler ' . $extracurricular->name . '.'
                ]
            ];
        }

        $selectedDate = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $attendanceDate = $selectedDate->format('Y-m-d');

        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        $existing = ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
            ->where('student_id', $student->id)
            ->where('attendance_date', $attendanceDate)
            ->first();

        if ($existing && $existing->status === 'hadir') {
            return [
                'status' => 409,
                'payload' => [
                    'success' => false,
                    'message' => 'Siswa ' . $student->name . ' sudah tercatat Hadir hari ini.',
                    'already_recorded' => true
                ]
            ];
        }

        ExtracurricularAttendance::updateOrCreate(
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

        return [
            'status' => 200,
            'payload' => [
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
            ]
        ];
    }

    /**
     * Menandai status kehadiran secara manual dari modal scanner.
     */
    public function markManualAttendance(Extracurricular $extracurricular, int $studentId, string $status, string $dateStr, ?string $notes = null): array
    {
        $student = Student::with('schoolClass')->find($studentId);
        if (!$student) {
            return [
                'status' => 404,
                'payload' => ['success' => false, 'message' => 'Siswa tidak ditemukan.']
            ];
        }

        if (!$extracurricular->students()->where('students.id', $student->id)->exists()) {
            return [
                'status' => 422,
                'payload' => ['success' => false, 'message' => 'Siswa tidak terdaftar di ekstrakurikuler ini.']
            ];
        }

        $attendanceDate = Carbon::parse($dateStr)->format('Y-m-d');
        $activeYear = AcademicYear::getActive();
        $activeSemester = Semester::getActive();

        if ($status === 'hapus') {
            ExtracurricularAttendance::where('extracurricular_id', $extracurricular->id)
                ->where('student_id', $student->id)
                ->where('attendance_date', $attendanceDate)
                ->delete();

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'message' => 'Presensi ' . $student->name . ' berhasil direset (Belum Absen).',
                    'student_id' => $student->id,
                    'status' => 'belum_absen'
                ]
            ];
        }

        ExtracurricularAttendance::updateOrCreate(
            [
                'extracurricular_id' => $extracurricular->id,
                'student_id' => $student->id,
                'attendance_date' => $attendanceDate,
            ],
            [
                'academic_year_id' => $activeYear?->id,
                'semester_id' => $activeSemester?->id,
                'status' => $status,
                'notes' => $notes
            ]
        );

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'message' => 'Status presensi ' . $student->name . ' berhasil diubah menjadi ' . ucfirst($status) . '.',
                'student_id' => $student->id,
                'status' => $status
            ]
        ];
    }

    /**
     * Mengambil dan mengagregasi data laporan presensi ekstrakurikuler (DRY untuk Preview & Print).
     */
    public function getReportData(Extracurricular $extracurricular, Teacher $teacher, ?string $startDateStr = null, ?string $endDateStr = null): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->startOfMonth();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->endOfMonth();

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

        return [
            'extracurricular' => $extracurricular,
            'students' => $students,
            'dates' => $dates,
            'attendanceData' => $attendanceData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'schoolIdentity' => $schoolIdentity,
            'teacher' => $teacher,
            'totalSessions' => $totalSessions,
            'totalMembers' => $totalMembers,
            'totalHadir' => $totalHadir,
            'totalSakit' => $totalSakit,
            'totalIzin' => $totalIzin,
            'totalAlpa' => $totalAlpa,
            'classAvgPercent' => $classAvgPercent,
            'requestInputs' => $requestInputs,
        ];
    }
}
