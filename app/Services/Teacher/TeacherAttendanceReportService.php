<?php

namespace App\Services\Teacher;

use App\Exports\AttendanceReportExport;
use App\Models\Attendance;
use App\Models\Calendar;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TeacherAttendanceReportService
{
    /**
     * Get or cache the base64 encoded school logo.
     */
    protected function getCachedLogoBase64(?string $logoPath): ?string
    {
        if (!$logoPath) {
            return null;
        }

        return Cache::remember('app_logo_base64_' . md5($logoPath), 3600, function () use ($logoPath) {
            if (Storage::disk('public')->exists($logoPath)) {
                try {
                    $logoData = Storage::disk('public')->get($logoPath);
                    return 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                } catch (\Throwable $e) {
                    return null;
                }
            }
            return null;
        });
    }

    /**
     * Prepare data for showAttendanceHistory view.
     */
    public function getAttendanceHistoryData($teacher, ?string $monthString): ?array
    {
        if (!$teacher || !$teacher->homeroomClass) {
            return null;
        }

        $class = $teacher->homeroomClass;
        $students = TeacherRoleContextService::getClassStudents($class);
        $studentIds = $students->pluck('id');

        $selectedDate = $monthString ? Carbon::parse($monthString) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();

        $allAttendancesInMonth = Attendance::withoutGlobalScope('academic_period')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $attendances = $allAttendancesInMonth
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $period = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !Calendar::isDateInHolidays($date, $holidays);
        });

        $attendanceSummary = [];
        $totalClassHadir = 0;
        $totalClassSakit = 0;
        $totalClassIzin = 0;
        $totalClassAlpa = 0;

        foreach ($students as $student) {
            $studentAttendances = $attendances->get($student->id, collect());
            $hadirCount = 0;
            $sakitCount = 0;
            $izinCount = 0;
            $alpaCount = 0;

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $attendanceRecord = $studentAttendances->get($dateString);

                if ($attendanceRecord) {
                    if (in_array($attendanceRecord->status, ['tepat_waktu', 'terlambat'])) {
                        $hadirCount++;
                    } elseif ($attendanceRecord->status === 'sakit') {
                        $sakitCount++;
                    } elseif (in_array($attendanceRecord->status, ['izin', 'izin_keluar'])) {
                        $izinCount++;
                    } elseif ($attendanceRecord->status === 'alpa') {
                        $alpaCount++;
                    }
                } else {
                    if ($date->isPast() && !$date->isToday()) {
                        $alpaCount++;
                    }
                }
            }
            $attendanceSummary[$student->id] = [
                'hadir' => $hadirCount,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
            ];

            $totalClassHadir += $hadirCount;
            $totalClassSakit += $sakitCount;
            $totalClassIzin += $izinCount;
            $totalClassAlpa += $alpaCount;
        }

        $totalEffectiveWorkdays = $period->count();
        $totalPossibleAttendance = $students->count() * $totalEffectiveWorkdays;
        $classAvgPercent = $totalPossibleAttendance > 0 ? round(($totalClassHadir / $totalPossibleAttendance) * 100, 1) : 0;

        return compact(
            'class',
            'students',
            'attendances',
            'period',
            'selectedDate',
            'attendanceSummary',
            'holidays',
            'selfStudyDays',
            'classAvgPercent',
            'totalEffectiveWorkdays',
            'totalClassSakit',
            'totalClassIzin',
            'totalClassAlpa'
        );
    }

    /**
     * Prepare data for printAttendance view.
     */
    public function getPrintMonthlyAttendanceData($teacher, ?string $monthString, string $paperSize = 'a4'): ?array
    {
        if (!$teacher || !$teacher->homeroomClass) {
            return null;
        }

        $class = $teacher->homeroomClass;
        $students = TeacherRoleContextService::getClassStudents($class);
        $studentIds = $students->pluck('id');

        $selectedDate = $monthString ? Carbon::parse($monthString) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();

        $allAttendancesInMonth = Attendance::withoutGlobalScope('academic_period')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $attendances = $allAttendancesInMonth
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $period = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !Calendar::isDateInHolidays($date, $holidays);
        });

        $effectiveDaysCount = $period->count();
        $totalStudents = $students->count();

        $attendanceSummary = [];
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlpa = 0;

        foreach ($students as $student) {
            $studentAttendances = $attendances->get($student->id, collect());
            $hadirCount = 0;
            $sakitCount = 0;
            $izinCount = 0;
            $alpaCount = 0;

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $isSelfStudy = Calendar::isDateInSelfStudy($date, $selfStudyDays);

                if ($isSelfStudy) {
                    $hadirCount++;
                } else {
                    $attendanceRecord = $studentAttendances->get($dateString);

                    if ($attendanceRecord) {
                        if (in_array($attendanceRecord->status, ['tepat_waktu', 'terlambat'])) {
                            $hadirCount++;
                        } elseif ($attendanceRecord->status === 'sakit') {
                            $sakitCount++;
                        } elseif (in_array($attendanceRecord->status, ['izin', 'izin_keluar'])) {
                            $izinCount++;
                        } elseif ($attendanceRecord->status === 'alpa') {
                            $alpaCount++;
                        }
                    } else {
                        if ($date->isPast() && !$date->isToday()) {
                            $alpaCount++;
                        }
                    }
                }
            }

            $totalAbsen = $sakitCount + $izinCount + $alpaCount;
            $jml = $totalAbsen;
            if ($effectiveDaysCount > 0) {
                $persen = round((($effectiveDaysCount - $totalAbsen) / $effectiveDaysCount) * 100, 0);
                $persen = max(0, min(100, $persen));
                $persenStr = $persen . '%';
            } else {
                $persen = 0;
                $persenStr = '0%';
            }

            $attendanceSummary[$student->id] = [
                'hadir' => $hadirCount,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
                'jml' => $jml,
                'persen' => $persen,
                'persen_str' => $persenStr,
            ];

            $totalHadir += $hadirCount;
            $totalSakit += $sakitCount;
            $totalIzin += $izinCount;
            $totalAlpa += $alpaCount;
        }

        $totalPossibleAttendance = $totalStudents * $effectiveDaysCount;
        $classAverageAttendance = $totalPossibleAttendance > 0 ? round(($totalHadir / $totalPossibleAttendance) * 100, 1) : 0;
        $perfectAttendanceCount = collect($attendanceSummary)->filter(fn($s) => $s['persen'] >= 100)->count();
        $needsAttentionCount = collect($attendanceSummary)->filter(fn($s) => $s['persen'] < 85)->count();

        $settings = Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = $this->getCachedLogoBase64($logoPath);

        $schoolName = $settings->get('school_name', config('app.name', 'SMP Negeri 1 Biau'));
        $schoolAddress = $settings->get('school_address', '');
        $schoolCity = $settings->get('school_city', 'Buol');
        $headmasterName = $settings->get('school_headmaster_name', '');
        $headmasterNip = $settings->get('school_headmaster_nip', '');
        $homeroomTeacherName = $teacher->name;
        $homeroomTeacherNip = $teacher->nip;
        $printDate = now()->translatedFormat('d F Y, H:i:s');

        return compact(
            'class',
            'students',
            'attendances',
            'period',
            'selectedDate',
            'attendanceSummary',
            'totalHadir',
            'totalSakit',
            'totalIzin',
            'totalAlpa',
            'teacher',
            'holidays',
            'selfStudyDays',
            'paperSize',
            'schoolName',
            'schoolAddress',
            'schoolCity',
            'logoBase64',
            'headmasterName',
            'headmasterNip',
            'homeroomTeacherName',
            'homeroomTeacherNip',
            'effectiveDaysCount',
            'totalStudents',
            'classAverageAttendance',
            'perfectAttendanceCount',
            'needsAttentionCount',
            'printDate'
        );
    }

    /**
     * Generate streamed PDF for trimester attendance report.
     */
    public function streamTrimesterPdf($teacher, int $trimester, int $year, string $paperSize = 'a4')
    {
        if (!$teacher || !$teacher->homeroomClass) {
            return null;
        }

        $class = $teacher->homeroomClass;

        switch ($trimester) {
            case 1:
                $months = [1, 2, 3];
                break;
            case 2:
                $months = [4, 5, 6];
                break;
            case 3:
                $months = [7, 8, 9];
                break;
            case 4:
            default:
                $months = [10, 11, 12];
                break;
        }

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

        $students = TeacherRoleContextService::getClassStudents($class, [
            'attendances' => function ($query) use ($year, $months) {
                $query->withoutGlobalScope('academic_period')
                    ->whereYear('attendance_time', $year)
                    ->whereIn(DB::raw('MONTH(attendance_time)'), $months);
            }
        ]);

        $reportData = $students->map(function ($student) use ($months, $trimesterMap, $year) {
            $studentData = [
                'name' => $student->name,
                'nis' => $student->nis,
                'monthly_data' => []
            ];

            // Pre-index student attendances by date for O(1) lookup
            $attendancesByDate = $student->attendances->keyBy(function ($att) {
                return Carbon::parse($att->attendance_time)->format('Y-m-d');
            });

            foreach ($months as $m) {
                $startDate = Carbon::create($year, $m, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
                $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);

                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !Calendar::isDateInHolidays($d, $holidays);
                });

                $hadir = 0;
                $sakit = 0;
                $izin = 0;
                $alpa = 0;

                foreach ($workdays as $wDate) {
                    $dateString = $wDate->format('Y-m-d');
                    $isSelfStudy = Calendar::isDateInSelfStudy($wDate, $selfStudyDays);

                    if ($isSelfStudy) {
                        $hadir++;
                    } else {
                        $attendanceRecord = $attendancesByDate->get($dateString);
                        $status = $attendanceRecord ? $attendanceRecord->status : null;

                        if (in_array($status, ['tepat_waktu', 'terlambat'])) {
                            $hadir++;
                        } elseif ($status === 'sakit') {
                            $sakit++;
                        } elseif (in_array($status, ['izin', 'izin_keluar'])) {
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
        $perfectAttendanceCount = $reportData->filter(function ($s) {
            return ($s->total_persen_num ?? 0) >= 100;
        })->count();
        $needsAttentionCount = $reportData->filter(function ($s) {
            return ($s->total_persen_num ?? 0) < 85;
        })->count();

        $settings = Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = $this->getCachedLogoBase64($logoPath);

        $pdfData = [
            'schoolName' => $settings->get('school_name', config('app.name')),
            'schoolAddress' => $settings->get('school_address'),
            'schoolCity' => $settings->get('school_city', 'Buol'),
            'logoBase64' => $logoBase64,
            'appName' => config('app.name', 'SIASEK'),
            'printDate' => now()->translatedFormat('d F Y, H:i:s'),
            'userRole' => 'Wali Kelas',
            'reportData' => $reportData,
            'className' => $class->name,
            'trimester' => $trimester,
            'year' => $year,
            'trimesterMap' => $trimesterMap,
            'months' => $months,
            'totalTrimesterEffectiveDays' => $totalTrimesterEffectiveDays,
            'totalStudents' => $totalStudents,
            'classAverageAttendance' => $classAverageAttendance,
            'perfectAttendanceCount' => $perfectAttendanceCount,
            'needsAttentionCount' => $needsAttentionCount,
            'homeroomTeacherName' => $teacher->name,
            'homeroomTeacherNip' => $teacher->nip,
            'headmasterName' => $settings->get('school_headmaster_name'),
            'headmasterNip' => $settings->get('school_headmaster_nip'),
            'paperSize' => $paperSize,
        ];

        $pdf = Pdf::loadView('admin.reports.triwulan_pdf', $pdfData);
        if (in_array($paperSize, ['folio', 'f4'])) {
            $pdf->setPaper([0, 0, 935.43, 609.45], 'landscape');
        } else {
            $pdf->setPaper('a4', 'landscape');
        }

        return $pdf->stream('laporan-triwulan-' . $class->name . '-T' . $trimester . '-' . $year . '.pdf');
    }

    /**
     * Compute analytics chart data for class/student across period types.
     */
    public function getChartData($teacher, array $params): ?array
    {
        if (!$teacher || !$teacher->homeroomClass) {
            return null;
        }

        $class = $teacher->homeroomClass;
        $cacheKey = "teacher:chart:{$teacher->id}:" . md5(json_encode($params));

        return Cache::remember($cacheKey, 60, function () use ($class, $params) {
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
                if ($s === 1) { // Ganjil: Jul - Dec
                    $months = [7, 8, 9, 10, 11, 12];
                } else { // Genap: Jan - Jun
                    $months = [1, 2, 3, 4, 5, 6];
                }
                foreach ($months as $m) {
                    $labels[] = Carbon::create()->month($m)->translatedFormat('F');
                }
            }

            $attendanceRelation = function ($query) use ($year, $months) {
                $query->withoutGlobalScope('academic_period')
                    ->whereYear('attendance_time', $year)
                    ->whereIn(DB::raw('MONTH(attendance_time)'), $months);
            };

            if ($params['target_type'] === 'student' && !empty($params['student_id'])) {
                $students = Student::where('id', $params['student_id'])->with(['attendances' => $attendanceRelation])->get();
            } else {
                $students = TeacherRoleContextService::getClassStudents($class, ['attendances' => $attendanceRelation]);
            }

            // Map student attendances keyed by student_id and date for O(1) lookups
            $studentAttendanceMap = [];
            foreach ($students as $s) {
                $studentAttendanceMap[$s->id] = $s->attendances->keyBy(fn($att) => Carbon::parse($att->attendance_time)->format('Y-m-d'));
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

                // Hanya hitung hari yang sudah berlalu (sampai hari ini)
                $passedWorkdays = $workdays->filter(function ($d) {
                    return $d->startOfDay() <= now()->startOfDay();
                });

                $hadirMonth = 0;
                $sakitMonth = 0;
                $izinMonth = 0;
                $alpaMonth = 0;

                foreach ($passedWorkdays as $wDate) {
                    $dateString = $wDate->format('Y-m-d');
                    $isSelfStudy = Calendar::isDateInSelfStudy($wDate, $selfStudyDays);

                    foreach ($students as $student) {
                        $attendanceRecord = $studentAttendanceMap[$student->id][$dateString] ?? null;

                        if ($isSelfStudy) {
                            $hadirMonth++;
                        } else {
                            $status = $attendanceRecord ? $attendanceRecord->status : null;
                            if (in_array($status, ['tepat_waktu', 'terlambat'])) {
                                $hadirMonth++;
                            } elseif ($status === 'sakit') {
                                $sakitMonth++;
                            } elseif (in_array($status, ['izin', 'izin_keluar'])) {
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
                    $p_hadir = round(($hadirMonth / $maxPossible) * 100, 1);
                    $p_sakit = round(($sakitMonth / $maxPossible) * 100, 1);
                    $p_izin  = round(($izinMonth / $maxPossible) * 100, 1);

                    // Hari tanpa keterangan/belum absen dianggap Alpa
                    $recorded = $hadirMonth + $sakitMonth + $izinMonth + $alpaMonth;
                    $unrecorded = $maxPossible - $recorded;
                    $totalAlpa = $alpaMonth + ($unrecorded > 0 ? $unrecorded : 0);

                    $p_alpa  = round(($totalAlpa / $maxPossible) * 100, 1);
                } else {
                    $p_hadir = 0;
                    $p_sakit = 0;
                    $p_izin = 0;
                    $p_alpa = 0;
                }

                $monthlyDataArray[] = [
                    'hadir' => $p_hadir,
                    'sakit' => $p_sakit,
                    'izin' => $p_izin,
                    'alpa' => $p_alpa,
                ];

                // Akumulasi sum
                $totalSum['hadir'] += $p_hadir;
                $totalSum['sakit'] += $p_sakit;
                $totalSum['izin'] += $p_izin;
                $totalSum['alpa'] += $p_alpa;
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
                'summary' => $totalSum,
                'monthly' => $monthlyDataArray,
            ];
        });
    }

    /**
     * Export attendance report to Excel.
     */
    public function downloadAttendanceExcel($teacher, ?string $monthString)
    {
        if (!$teacher || !$teacher->homeroomClass) {
            return null;
        }

        $class = $teacher->homeroomClass;
        $students = TeacherRoleContextService::getClassStudents($class);
        $studentIds = $students->pluck('id');

        $selectedDate = $monthString ? Carbon::parse($monthString) : Carbon::now();
        $startDate = $selectedDate->copy()->startOfMonth();
        $endDate = $selectedDate->copy()->endOfMonth();

        $allAttendancesInMonth = Attendance::withoutGlobalScope('academic_period')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get();

        $attendances = $allAttendancesInMonth
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $holidays = Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $period = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !Calendar::isDateInHolidays($date, $holidays);
        });

        $attendanceSummary = [];
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlpa = 0;

        foreach ($students as $student) {
            $studentAttendances = $attendances->get($student->id, collect());
            $hadirCount = 0;
            $sakitCount = 0;
            $izinCount = 0;
            $alpaCount = 0;

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                $attendanceRecord = $studentAttendances->get($dateString);

                if ($attendanceRecord) {
                    if (in_array($attendanceRecord->status, ['tepat_waktu', 'terlambat'])) {
                        $hadirCount++;
                    } elseif ($attendanceRecord->status === 'sakit') {
                        $sakitCount++;
                    } elseif (in_array($attendanceRecord->status, ['izin', 'izin_keluar'])) {
                        $izinCount++;
                    } elseif ($attendanceRecord->status === 'alpa') {
                        $alpaCount++;
                    }
                } else {
                    if ($date->isPast() && !$date->isToday()) {
                        $alpaCount++;
                    }
                }
            }
            $attendanceSummary[$student->id] = [
                'hadir' => $hadirCount,
                'sakit' => $sakitCount,
                'izin' => $izinCount,
                'alpa' => $alpaCount,
            ];
            $totalHadir += $hadirCount;
            $totalSakit += $sakitCount;
            $totalIzin += $izinCount;
            $totalAlpa += $alpaCount;
        }

        $settings = Setting::whereIn('key', [
            'app_logo', 'school_name', 'school_address', 'school_phone',
            'school_email', 'school_headmaster_name', 'school_headmaster_nip'
        ])->get();

        $schoolIdentity = [
            'logo' => $settings->firstWhere('key', 'app_logo')->value ?? null,
            'name' => $settings->firstWhere('key', 'school_name')->value ?? null,
            'address' => $settings->firstWhere('key', 'school_address')->value ?? null,
            'phone' => $settings->firstWhere('key', 'school_phone')->value ?? null,
            'email' => $settings->firstWhere('key', 'school_email')->value ?? null,
            'headmaster_name' => $settings->firstWhere('key', 'school_headmaster_name')->value ?? null,
            'headmaster_nip' => $settings->firstWhere('key', 'school_headmaster_nip')->value ?? null,
        ];

        $fileName = 'Laporan_Presensi_' . str_replace(' ', '_', $class->name) . '_' . $selectedDate->format('F_Y') . '.xlsx';

        return Excel::download(new AttendanceReportExport(
            $class,
            $students,
            $attendances,
            $period,
            $selectedDate,
            $attendanceSummary,
            $totalHadir,
            $totalSakit,
            $totalIzin,
            $totalAlpa,
            $teacher,
            $schoolIdentity,
            $holidays,
            $selfStudyDays
        ), $fileName);
    }
}
