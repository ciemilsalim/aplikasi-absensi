<?php

namespace App\Services\Admin;

use App\Models\Attendance;
use App\Models\Calendar;
use App\Models\Cocurricular;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SubjectAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminReportService
{
    /**
     * Helper ekspresi SQL ekstraksi bulan yang kompatibel dengan MySQL dan SQLite.
     */
    protected function getMonthSqlExpression(): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return "CAST(strftime('%m', attendance_time) AS INTEGER)";
        }
        return "MONTH(attendance_time)";
    }

    /**
     * Menyiapkan opsi data untuk form pembuatan laporan.
     */
    public function getReportFormOptions(): array
    {
        $classes = SchoolClass::orderBy('name')->get();
        $students = Student::active()->with('schoolClass')->orderBy('name')->get();
        $cocurriculars = Cocurricular::orderBy('title')->get();

        return compact('classes', 'students', 'cocurriculars');
    }

    /**
     * Menyiapkan opsi data untuk antarmuka visual chart.
     */
    public function getChartFormOptions(): array
    {
        $classes = SchoolClass::orderBy('name')->get();
        $students = Student::active()->with('schoolClass')->orderBy('name')->get();

        return compact('classes', 'students');
    }

    /**
     * Menghitung dataset analitik presensi untuk Chart.js.
     */
    public function getChartAnalyticsData(array $params): array
    {
        $year = (int) $params['year'];
        $months = [];
        $labels = [];

        if ($params['period_type'] === 'month') {
            $months = [(int) $params['period_value']];
            $labels = [Carbon::create()->month($months[0])->translatedFormat('F')];
        } elseif ($params['period_type'] === 'trimester') {
            $t = (int) $params['period_value'];
            $months = [($t - 1) * 3 + 1, ($t - 1) * 3 + 2, ($t - 1) * 3 + 3];
            foreach ($months as $m) {
                $labels[] = Carbon::create()->month($m)->translatedFormat('F');
            }
        } elseif ($params['period_type'] === 'semester') {
            $s = (int) $params['period_value'];
            $months = ($s === 1) ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
            foreach ($months as $m) {
                $labels[] = Carbon::create()->month($m)->translatedFormat('F');
            }
        }

        $monthExpr = $this->getMonthSqlExpression();

        if ($params['target_type'] === 'class') {
            $targetClass = SchoolClass::withoutGlobalScopes()->find($params['school_class_id']);
            $students = $targetClass ? $targetClass->students()->with(['attendances' => function ($query) use ($year, $months, $monthExpr) {
                $query->whereYear('attendance_time', $year)
                      ->whereIn(DB::raw($monthExpr), $months);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            }])->get() : collect();

            if ($students->isEmpty() && $targetClass) {
                $q = Student::where('school_class_id', $targetClass->id);
                $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
                $isCurrentActiveSemester = $activeSemesterId ? (bool)Semester::where('id', $activeSemesterId)->value('is_active') : true;
                if ($isCurrentActiveSemester) {
                    $q->active();
                }
                $students = $q->with(['attendances' => function ($query) use ($year, $months, $monthExpr) {
                    $query->whereYear('attendance_time', $year)
                          ->whereIn(DB::raw($monthExpr), $months);
                    if (session('active_semester_id')) {
                        $query->where('semester_id', session('active_semester_id'));
                    }
                }])->get();
            }
        } else {
            $students = Student::where('id', $params['student_id'])->with(['attendances' => function ($query) use ($year, $months, $monthExpr) {
                $query->whereYear('attendance_time', $year)
                      ->whereIn(DB::raw($monthExpr), $months);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            }])->get();
        }

        $monthlyDataArray = [];
        $totalSum = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

        foreach ($months as $m) {
            $startDate = Carbon::create($year, $m, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
            $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
            $period = CarbonPeriod::create($startDate, $endDate);

            $workdays = collect($period)->filter(function ($d) use ($holidays) {
                return !$d->isWeekend() && !Calendar::isDateInHolidays($d, $holidays);
            });

            // Hanya hitung hari kerja yang sudah berlalu
            $passedWorkdays = $workdays->filter(function ($d) {
                return $d->startOfDay() <= now()->startOfDay();
            });

            $hadirMonth = 0; $sakitMonth = 0; $izinMonth = 0; $alpaMonth = 0;

            foreach ($passedWorkdays as $wDate) {
                $dateString = $wDate->format('Y-m-d');
                $isSelfStudy = Calendar::isDateInSelfStudy($wDate, $selfStudyDays);

                foreach ($students as $student) {
                    $attendanceRecord = $student->attendances->firstWhere(function ($item) use ($dateString) {
                        return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                    });

                    if ($isSelfStudy) {
                        $hadirMonth++;
                    } else {
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        if (in_array($status, ['tepat_waktu', 'terlambat'])) {
                            $hadirMonth++;
                        } elseif ($status === 'sakit') {
                            $sakitMonth++;
                        } elseif ($status === 'izin') {
                            $izinMonth++;
                        } elseif ($status === 'alpa') {
                            $alpaMonth++;
                        }
                    }
                }
            }

            $totalStudentsCount = count($students);
            $maxPossible = $passedWorkdays->count() * $totalStudentsCount;

            if ($maxPossible > 0) {
                $pHadir = round(($hadirMonth / $maxPossible) * 100, 1);
                $pSakit = round(($sakitMonth / $maxPossible) * 100, 1);
                $pIzin  = round(($izinMonth / $maxPossible) * 100, 1);

                $recorded = $hadirMonth + $sakitMonth + $izinMonth + $alpaMonth;
                $unrecorded = $maxPossible - $recorded;
                $totalAlpa = $alpaMonth + ($unrecorded > 0 ? $unrecorded : 0);

                $pAlpa  = round(($totalAlpa / $maxPossible) * 100, 1);
            } else {
                $pHadir = 0; $pSakit = 0; $pIzin = 0; $pAlpa = 0;
            }

            $monthlyDataArray[] = [
                'hadir' => $pHadir,
                'sakit' => $pSakit,
                'izin' => $pIzin,
                'alpa' => $pAlpa,
            ];

            $totalSum['hadir'] += $pHadir;
            $totalSum['sakit'] += $pSakit;
            $totalSum['izin'] += $pIzin;
            $totalSum['alpa'] += $pAlpa;
        }

        $numMonths = count($months);
        if ($numMonths > 0) {
            $totalSum['hadir'] = round($totalSum['hadir'] / $numMonths, 1);
            $totalSum['sakit'] = round($totalSum['sakit'] / $numMonths, 1);
            $totalSum['izin'] = round($totalSum['izin'] / $numMonths, 1);
            $totalSum['alpa'] = round($totalSum['alpa'] / $numMonths, 1);
        }

        return [
            'labels' => $labels,
            'monthly' => $monthlyDataArray,
            'summary' => $totalSum
        ];
    }

    /**
     * Mengambil data umum kop dan identitas sekolah untuk rendering PDF.
     */
    public function getCommonPdfData(): array
    {
        $settings = Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            try {
                $logoData = Storage::disk('public')->get($logoPath);
                $logoBase64 = 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            } catch (\Exception $e) {
                $logoBase64 = null;
            }
        }

        $userRole = Auth::check() ? ucfirst(Auth::user()->role) : 'Tamu';

        return [
            'schoolName' => $settings->get('school_name', config('app.name')),
            'schoolAddress' => $settings->get('school_address'),
            'schoolCity' => $settings->get('school_city', 'Buol'),
            'logoBase64' => $logoBase64,
            'appName' => config('app.name', 'SIASEK'),
            'printDate' => now()->translatedFormat('d F Y, H:i:s'),
            'userRole' => $userRole,
            'headmasterName' => $settings->get('school_headmaster_name', '-'),
            'headmasterNip' => $settings->get('school_headmaster_nip', '-'),
        ];
    }

    /**
     * Membuat dokumen PDF rekap bulanan kelas pada proyek kokurikuler.
     */
    public function generateCocurricularMonthlyReport(Request $request)
    {
        $class = SchoolClass::withoutGlobalScopes()->findOrFail($request->school_class_id);
        $cocurricular = Cocurricular::findOrFail($request->cocurricular_id);
        $date = Carbon::createFromFormat('Y-m', $request->month);
        $monthName = $date->translatedFormat('F Y');

        $schedules = Schedule::where('schedule_type', 'cocurricular')
            ->where('cocurricular_id', $cocurricular->id)
            ->where('school_class_id', $class->id)
            ->get();

        $scheduleIds = $schedules->pluck('id')->toArray();
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $students = $class->students()->orderBy('name')->get();
        if ($students->isEmpty()) {
            $q = Student::where('school_class_id', $class->id);
            $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool)Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            $students = $q->orderBy('name')->get();
        }

        $attendances = SubjectAttendance::whereIn('schedule_id', $scheduleIds)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get();

        $reportData = $students->map(function ($student) use ($attendances) {
            $studentAtts = $attendances->where('student_id', $student->id);
            return (object) [
                'name' => $student->name,
                'nis' => $student->nis,
                'hadir' => $studentAtts->where('status', 'hadir')->count(),
                'sakit' => $studentAtts->where('status', 'sakit')->count(),
                'izin' => $studentAtts->where('status', 'izin')->count(),
                'alpa' => $studentAtts->whereIn('status', ['alpa', 'bolos'])->count(),
            ];
        });

        $pdfData = $this->getCommonPdfData();
        $pdfData['reportData'] = $reportData;
        $pdfData['className'] = $class->name . ' - Kokurikuler: ' . $cocurricular->title;
        $pdfData['monthName'] = $monthName;

        $pdf = Pdf::loadView('admin.reports.pdf', $pdfData);
        return $pdf->stream('laporan-kokurikuler-' . $class->name . '-' . $monthName . '.pdf');
    }

    /**
     * Membuat dokumen PDF rekap bulanan presensi kelas.
     */
    public function generateClassMonthlyReport(Request $request)
    {
        $class = SchoolClass::withoutGlobalScopes()->findOrFail($request->school_class_id);
        $date = Carbon::createFromFormat('Y-m', $request->month);
        $monthName = $date->translatedFormat('F Y');

        $students = $class->students()
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereYear('attendance_time', $date->year)
                    ->whereMonth('attendance_time', $date->month);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            }])
            ->orderBy('name')
            ->get();

        if ($students->isEmpty()) {
            $q = Student::where('school_class_id', $class->id);
            $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool)Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            $students = $q->with(['attendances' => function ($query) use ($date) {
                    $query->whereYear('attendance_time', $date->year)
                        ->whereMonth('attendance_time', $date->month);
                    if (session('active_semester_id')) {
                        $query->where('semester_id', session('active_semester_id'));
                    }
                }])
                ->orderBy('name')
                ->get();
        }

        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $workdays = collect($period)->filter(function ($d) use ($holidays) {
            return !$d->isWeekend() && !Calendar::isDateInHolidays($d, $holidays);
        });

        $reportData = $students->map(function ($student) use ($workdays, $selfStudyDays) {
            $attendancesInMonth = $student->attendances;
            $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0;

            foreach ($workdays as $wDate) {
                $dateString = $wDate->format('Y-m-d');
                $attendanceRecord = $attendancesInMonth->firstWhere(function ($item) use ($dateString) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                });

                $isSelfStudy = Calendar::isDateInSelfStudy($wDate, $selfStudyDays);

                if ($isSelfStudy) {
                    $hadir++;
                } else {
                    $status = $attendanceRecord ? $attendanceRecord->status : null;
                    if (in_array($status, ['tepat_waktu', 'terlambat'])) {
                        $hadir++;
                    } elseif ($status === 'sakit') {
                        $sakit++;
                    } elseif ($status === 'izin') {
                        $izin++;
                    } elseif ($status === 'alpa') {
                        $alpa++;
                    }
                }
            }

            return (object) [
                'name' => $student->name,
                'nis' => $student->nis,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
            ];
        });

        $pdfData = $this->getCommonPdfData();
        $pdfData['reportData'] = $reportData;
        $pdfData['className'] = $class->name;
        $pdfData['monthName'] = $monthName;

        $pdf = Pdf::loadView('admin.reports.pdf', $pdfData);
        return $pdf->stream('laporan-kelas-' . $class->name . '-' . $date->format('F-Y') . '.pdf');
    }

    /**
     * Membuat dokumen PDF rekap triwulan kelas.
     */
    public function generateClassTrimesterReport(Request $request)
    {
        $class = SchoolClass::withoutGlobalScopes()->findOrFail($request->school_class_id);
        $year = $request->year;
        $trimester = $request->trimester;

        $months = match ((int)$trimester) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            default => [10, 11, 12],
        };

        $monthNames = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];

        $trimesterMap = [];
        foreach ($months as $m) {
            $effectiveDays = Setting::where('key', 'effective_days_' . $year . '_' . $m)->value('value');
            if ($effectiveDays === null) {
                $effectiveDays = Setting::where('key', 'effective_days_' . $m)->value('value');
            }

            if (empty($effectiveDays) || $effectiveDays == 0) {
                $startDate = Carbon::create($year, $m, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);
                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !Calendar::isDateInHolidays($d, $holidays);
                });
                $effectiveDays = $workdays->count();
            }

            $trimesterMap[$m] = [
                'name' => $monthNames[$m],
                'effective_days' => $effectiveDays !== null ? (int)$effectiveDays : 0
            ];
        }

        $monthExpr = $this->getMonthSqlExpression();

        $students = $class->students()
            ->with(['attendances' => function ($query) use ($year, $months, $monthExpr) {
                $query->whereYear('attendance_time', $year)
                      ->whereIn(DB::raw($monthExpr), $months);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            }])
            ->orderBy('name')
            ->get();

        if ($students->isEmpty()) {
            $q = Student::where('school_class_id', $class->id);
            $activeSemesterId = session('active_semester_id') ?? Semester::where('is_active', true)->value('id');
            $isCurrentActiveSemester = $activeSemesterId ? (bool)Semester::where('id', $activeSemesterId)->value('is_active') : true;
            if ($isCurrentActiveSemester) {
                $q->active();
            }
            $students = $q->with(['attendances' => function ($query) use ($year, $months, $monthExpr) {
                    $query->whereYear('attendance_time', $year)
                          ->whereIn(DB::raw($monthExpr), $months);
                    if (session('active_semester_id')) {
                        $query->where('semester_id', session('active_semester_id'));
                    }
                }])
                ->orderBy('name')
                ->get();
        }

        $reportData = $students->map(function ($student) use ($months, $trimesterMap, $year) {
            $studentData = [
                'name' => $student->name,
                'nis' => $student->nis,
                'monthly_data' => []
            ];

            foreach ($months as $m) {
                $attendancesInMonth = $student->attendances->filter(function ($att) use ($m) {
                    return Carbon::parse($att->attendance_time)->month == $m;
                });

                $startDate = Carbon::create($year, $m, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
                $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);

                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !Calendar::isDateInHolidays($d, $holidays);
                });

                $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0;

                foreach ($workdays as $wDate) {
                    $dateString = $wDate->format('Y-m-d');
                    $attendanceRecord = $attendancesInMonth->firstWhere(function ($item) use ($dateString) {
                        return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                    });

                    $isSelfStudy = Calendar::isDateInSelfStudy($wDate, $selfStudyDays);

                    if ($isSelfStudy) {
                        $hadir++;
                    } else {
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        if (in_array($status, ['tepat_waktu', 'terlambat'])) {
                            $hadir++;
                        } elseif ($status === 'sakit') {
                            $sakit++;
                        } elseif ($status === 'izin') {
                            $izin++;
                        } elseif ($status === 'alpa') {
                            $alpa++;
                        }
                    }
                }

                $effDays = $trimesterMap[$m]['effective_days'];
                $totalAbsen = $sakit + $izin + $alpa;
                $jml = $totalAbsen;

                if ($effDays > 0) {
                    $persen = (($effDays - $jml) / $effDays) * 100;
                    $persenStr = round($persen, 0) . '%';
                } else {
                    $persenStr = '0%';
                }

                $studentData['total_alpa'] = ($studentData['total_alpa'] ?? 0) + $alpa;
                $studentData['total_izin'] = ($studentData['total_izin'] ?? 0) + $izin;
                $studentData['total_sakit'] = ($studentData['total_sakit'] ?? 0) + $sakit;
                $studentData['total_jml'] = ($studentData['total_jml'] ?? 0) + $jml;
                $studentData['total_effective_days'] = ($studentData['total_effective_days'] ?? 0) + $effDays;

                $studentData['monthly_data'][$m] = [
                    'alpa' => $alpa,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'jml' => $jml,
                    'persen' => $persenStr
                ];
            }

            if (($studentData['total_effective_days'] ?? 0) > 0) {
                $totPersen = (($studentData['total_effective_days'] - $studentData['total_jml']) / $studentData['total_effective_days']) * 100;
                $studentData['total_persen'] = round($totPersen, 0) . '%';
                $studentData['total_persen_num'] = $totPersen;
            } else {
                $studentData['total_persen'] = '0%';
                $studentData['total_persen_num'] = 0;
            }

            return (object)$studentData;
        });

        $totalTrimesterEffectiveDays = 0;
        foreach ($months as $m) {
            $totalTrimesterEffectiveDays += $trimesterMap[$m]['effective_days'];
        }

        $totalStudents = $reportData->count();
        $classAverageAttendance = $totalStudents > 0 ? round($reportData->avg('total_persen_num'), 1) : 0;
        $perfectAttendanceCount = $reportData->filter(fn($s) => ($s->total_persen_num ?? 0) >= 100)->count();
        $needsAttentionCount = $reportData->filter(fn($s) => ($s->total_persen_num ?? 0) < 85)->count();

        $pdfData = $this->getCommonPdfData();
        $pdfData['reportData'] = $reportData;
        $pdfData['className'] = $class->name;
        $pdfData['trimester'] = $trimester;
        $pdfData['year'] = $year;
        $pdfData['trimesterMap'] = $trimesterMap;
        $pdfData['months'] = $months;
        $pdfData['totalTrimesterEffectiveDays'] = $totalTrimesterEffectiveDays;
        $pdfData['totalStudents'] = $totalStudents;
        $pdfData['classAverageAttendance'] = $classAverageAttendance;
        $pdfData['perfectAttendanceCount'] = $perfectAttendanceCount;
        $pdfData['needsAttentionCount'] = $needsAttentionCount;

        $homeroomTeacher = $class->homeroomTeacher;
        $pdfData['homeroomTeacherName'] = $homeroomTeacher->name ?? null;
        $pdfData['homeroomTeacherNip'] = $homeroomTeacher->nip ?? null;

        $paperSize = strtolower($request->input('paper_size', 'a4'));
        $pdfData['paperSize'] = $paperSize;

        $pdf = Pdf::loadView('admin.reports.triwulan_pdf', $pdfData);
        if (in_array($paperSize, ['folio', 'f4'])) {
            $pdf->setPaper([0, 0, 935.43, 609.45], 'landscape');
        } else {
            $pdf->setPaper('a4', 'landscape');
        }

        return $pdf->stream('laporan-triwulan-' . $class->name . '-T' . $trimester . '-' . $year . '.pdf');
    }

    /**
     * Membuat dokumen PDF detail presensi siswa.
     */
    public function generateStudentDetailedReport(Request $request)
    {
        $student = Student::with('schoolClass.homeroomTeacher')->findOrFail($request->student_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $attendances = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->when(session('active_semester_id'), function ($q) {
                return $q->where('semester_id', session('active_semester_id'));
            })
            ->orderBy('attendance_time', 'asc')
            ->get();

        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $workdays = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !Calendar::isDateInHolidays($date, $holidays);
        });

        $pdfData = $this->getCommonPdfData();
        $pdfData['student'] = $student;
        $pdfData['attendances'] = $attendances;
        $pdfData['workdays'] = $workdays;
        $pdfData['selfStudyDays'] = $selfStudyDays;
        $pdfData['startDate'] = $startDate->translatedFormat('d F Y');
        $pdfData['endDate'] = $endDate->translatedFormat('d F Y');

        $homeroomTeacher = $student->schoolClass?->homeroomTeacher;
        $pdfData['homeroomTeacherName'] = $homeroomTeacher->name ?? '-';
        $pdfData['homeroomTeacherNip'] = $homeroomTeacher->nip ?? null;

        $pdf = Pdf::loadView('admin.reports.student_pdf', $pdfData);
        return $pdf->stream('laporan-detail-' . $student->name . '.pdf');
    }

    /**
     * Membuat dokumen PDF rekap keterlambatan seluruh sekolah.
     */
    public function generateSchoolLatenessReport(Request $request)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $latenessData = Student::with('schoolClass')
            ->whereHas('attendances', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'terlambat')
                    ->whereBetween('attendance_time', [$startDate, $endDate]);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            })
            ->withCount(['attendances as late_count' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'terlambat')
                    ->whereBetween('attendance_time', [$startDate, $endDate]);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            }])
            ->orderByDesc('late_count')
            ->get();

        $pdfData = $this->getCommonPdfData();
        $pdfData['latenessData'] = $latenessData;
        $pdfData['startDate'] = $startDate->translatedFormat('d F Y');
        $pdfData['endDate'] = $endDate->translatedFormat('d F Y');

        $pdf = Pdf::loadView('admin.reports.lateness_pdf', $pdfData);
        return $pdf->stream('laporan-keterlambatan.pdf');
    }

    /**
     * Membuat dokumen PDF rekap siswa yang tidak absen pulang.
     */
    public function generateNoCheckoutReport(Request $request)
    {
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $attendancesQuery = Attendance::with(['student.schoolClass'])
            ->whereIn('attendances.status', ['tepat_waktu', 'terlambat'])
            ->whereNull('attendances.checkout_time')
            ->whereBetween('attendances.attendance_time', [$startDate, $endDate])
            ->when(session('active_semester_id'), function ($q) {
                return $q->where('attendances.semester_id', session('active_semester_id'));
            })
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('school_classes', 'students.school_class_id', '=', 'school_classes.id')
            ->orderBy('school_classes.name', 'asc')
            ->orderBy('students.name', 'asc')
            ->select('attendances.*')
            ->get();

        $groupedAttendances = $attendancesQuery->groupBy(function ($attendance) {
            return $attendance->student->schoolClass->name ?? 'Belum Ada Kelas';
        });

        $pdfData = $this->getCommonPdfData();
        $pdfData['groupedAttendances'] = $groupedAttendances;
        $pdfData['startDate'] = $startDate->translatedFormat('d F Y');
        $pdfData['endDate'] = $endDate->translatedFormat('d F Y');

        $pdf = Pdf::loadView('admin.reports.no_checkout_pdf', $pdfData);
        return $pdf->stream('laporan-tidak-absen-pulang.pdf');
    }
}
