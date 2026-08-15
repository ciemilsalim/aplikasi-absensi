<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Menampilkan form untuk memilih parameter laporan.
     */
    public function create()
    {
        $classes = SchoolClass::orderBy('name')->get();
        $students = Student::with('schoolClass')->orderBy('name')->get();

        return view('admin.reports.create', compact('classes', 'students'));
    }

    /**
     * Menampilkan antarmuka Analitik Visual (Chart).
     */
    public function charts()
    {
        $classes = SchoolClass::orderBy('name')->get();
        $students = Student::with('schoolClass')->orderBy('name')->get();
        
        return view('admin.reports.charts', compact('classes', 'students'));
    }

    /**
     * Memproses data untuk merender Chart.
     */
    public function chartData(Request $request)
    {
        $params = $request->validate([
            'target_type' => 'required|in:class,student',
            'period_type' => 'required|in:month,trimester,semester',
            'year' => 'required|integer',
            'period_value' => 'required|integer',
            'school_class_id' => 'required_if:target_type,class|nullable|exists:school_classes,id',
            'student_id' => 'required_if:target_type,student|nullable|exists:students,id',
        ]);

        $year = (int) $params['year'];
        $months = [];
        $labels = [];

        if ($params['period_type'] === 'month') {
            $months = [(int) $params['period_value']];
            $labels = [Carbon::create()->month($months[0])->translatedFormat('F')];
        } elseif ($params['period_type'] === 'trimester') {
            $t = (int) $params['period_value'];
            $months = [($t - 1) * 3 + 1, ($t - 1) * 3 + 2, ($t - 1) * 3 + 3];
            foreach($months as $m) $labels[] = Carbon::create()->month($m)->translatedFormat('F');
        } elseif ($params['period_type'] === 'semester') {
            $s = (int) $params['period_value'];
            if ($s === 1) { // Ganjil: Jul - Dec
                $months = [7, 8, 9, 10, 11, 12];
            } else { // Genap: Jan - Jun
                $months = [1, 2, 3, 4, 5, 6];
                // $year = $year + 1; // Opsional jika Genap dianggap tahun kalender berikutnya, asumsi menggunakan tahun yang dipilih UI
            }
            foreach($months as $m) $labels[] = Carbon::create()->month($m)->translatedFormat('F');
        }

        $studentsQuery = Student::query();
        if ($params['target_type'] === 'class') {
            $studentsQuery->where('school_class_id', $params['school_class_id']);
        } else {
            $studentsQuery->where('id', $params['student_id']);
        }

        // Ambil data absensi
        $students = $studentsQuery->with(['attendances' => function ($query) use ($year, $months) {
            $query->whereYear('attendance_time', $year)
                  ->whereIn(\DB::raw('MONTH(attendance_time)'), $months);
            if (session('active_semester_id')) {
                $query->where('semester_id', session('active_semester_id'));
            }
        }])->get();

        $monthlyDataArray = [];
        $totalSum = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

        foreach ($months as $m) {
            $startDate = Carbon::create($year, $m, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
            $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
            $period = CarbonPeriod::create($startDate, $endDate);

            $workdays = collect($period)->filter(function ($d) use ($holidays) {
                return !$d->isWeekend() && !\App\Models\Calendar::isDateInHolidays($d, $holidays);
            });

            // Hanya hitung hari yang sudah berlalu (sampai hari ini)
            $passedWorkdays = $workdays->filter(function ($d) {
                return $d->startOfDay() <= now()->startOfDay();
            });

            $hadirMonth = 0; $sakitMonth = 0; $izinMonth = 0; $alpaMonth = 0;

            foreach ($passedWorkdays as $wDate) {
                $dateString = $wDate->format('Y-m-d');
                $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($wDate, $selfStudyDays);

                foreach ($students as $student) {
                    $attendanceRecord = $student->attendances->firstWhere(function($item) use ($dateString) {
                        return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                    });

                    if ($isSelfStudy) {
                        $hadirMonth++;
                    } else {
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        if (in_array($status, ['tepat_waktu', 'terlambat'])) $hadirMonth++;
                        elseif ($status === 'sakit') $sakitMonth++;
                        elseif ($status === 'izin') $izinMonth++;
                        elseif ($status === 'alpa') $alpaMonth++;
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
                $p_hadir = 0; $p_sakit = 0; $p_izin = 0; $p_alpa = 0;
            }

            $monthlyDataArray[] = [
                'hadir' => $p_hadir,
                'sakit' => $p_sakit,
                'izin' => $p_izin,
                'alpa' => $p_alpa,
            ];

            // Akumulasi sum menggunakan rata-rata atau total persentase
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

        return response()->json([
            'labels' => $labels,
            'monthly' => $monthlyDataArray,
            'summary' => $totalSum
        ]);
    }

    /**
     * Membuat dan menampilkan laporan dalam format PDF berdasarkan jenisnya.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:class_monthly,class_trimester,student_detailed,school_lateness,school_no_checkout',
            'month' => 'required_if:report_type,class_monthly|date_format:Y-m',
            'school_class_id' => 'required_if:report_type,class_monthly,class_trimester|exists:school_classes,id',
            'trimester' => 'required_if:report_type,class_trimester|in:1,2,3,4',
            'year' => 'required_if:report_type,class_trimester|integer|min:2000',
            'student_id' => 'required_if:report_type,student_detailed|exists:students,id',
            'start_date' => 'required_if:report_type,student_detailed,school_lateness,school_no_checkout|date',
            'end_date' => 'required_if:report_type,student_detailed,school_lateness,school_no_checkout|date|after_or_equal:start_date',
        ]);

        $reportType = $request->report_type;

        if ($reportType === 'class_monthly') {
            return $this->generateClassMonthlyReport($request);
        }
        elseif ($reportType === 'class_trimester') {
            return $this->generateClassTrimesterReport($request);
        }
        elseif ($reportType === 'student_detailed') {
            return $this->generateStudentDetailedReport($request);
        }
        elseif ($reportType === 'school_lateness') {
            return $this->generateSchoolLatenessReport($request);
        }
        elseif ($reportType === 'school_no_checkout') {
            return $this->generateNoCheckoutReport($request);
        }

        return redirect()->back()->with('error', 'Jenis laporan tidak valid.');
    }

    /**
     * Membuat laporan rekap kehadiran bulanan per kelas.
     */
    private function generateClassMonthlyReport(Request $request)
    {
        // ... (Fungsi ini tidak berubah) ...
        $class = SchoolClass::findOrFail($request->school_class_id);
        $date = Carbon::createFromFormat('Y-m', $request->month);
        $monthName = $date->translatedFormat('F Y');

        $students = Student::where('school_class_id', $class->id)
            ->with(['attendances' => function ($query) use ($date) {
            $query->whereYear('attendance_time', $date->year)
                ->whereMonth('attendance_time', $date->month);
            if (session('active_semester_id')) {
                $query->where('semester_id', session('active_semester_id'));
            }
        }])
            ->orderBy('name')
            ->get();

        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $workdays = collect($period)->filter(function ($d) use ($holidays) {
            return !$d->isWeekend() && !\App\Models\Calendar::isDateInHolidays($d, $holidays);
        });

        $reportData = $students->map(function ($student) use ($workdays, $selfStudyDays) {
            $attendancesInMonth = $student->attendances;
            $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0;

            foreach ($workdays as $wDate) {
                $dateString = $wDate->format('Y-m-d');
                $attendanceRecord = $attendancesInMonth->firstWhere(function($item) use ($dateString) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                });
                
                $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($wDate, $selfStudyDays);
                
                if ($isSelfStudy) {
                    $hadir++;
                } else {
                    $status = $attendanceRecord ? $attendanceRecord->status : null;
                    if (in_array($status, ['tepat_waktu', 'terlambat'])) $hadir++;
                    elseif ($status === 'sakit') $sakit++;
                    elseif ($status === 'izin') $izin++;
                    elseif ($status === 'alpa') $alpa++;
                }
            }

            return (object)[
                'name' => $student->name, 'nis' => $student->nis,
                'hadir' => $hadir, 'sakit' => $sakit, 'izin' => $izin, 'alpa' => $alpa,
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
     * Membuat laporan rekap kehadiran triwulan kelas.
     */
    private function generateClassTrimesterReport(Request $request)
    {
        $class = SchoolClass::findOrFail($request->school_class_id);
        $year = $request->year;
        $trimester = $request->trimester;
        
        $months = [];
        if ($trimester == 1) $months = [1, 2, 3];
        elseif ($trimester == 2) $months = [4, 5, 6];
        elseif ($trimester == 3) $months = [7, 8, 9];
        elseif ($trimester == 4) $months = [10, 11, 12];

        // Dapatkan hari efektif per bulan
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
                $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);
                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !\App\Models\Calendar::isDateInHolidays($d, $holidays);
                });
                $effectiveDays = $workdays->count();
            }

            $trimesterMap[$m] = [
                'name' => $monthNames[$m],
                'effective_days' => $effectiveDays !== null ? (int)$effectiveDays : 0
            ];
        }

        $students = Student::where('school_class_id', $class->id)
            ->with(['attendances' => function ($query) use ($year, $months) {
                $query->whereYear('attendance_time', $year)
                      ->whereIn(\DB::raw('MONTH(attendance_time)'), $months);
                if (session('active_semester_id')) {
                    $query->where('semester_id', session('active_semester_id'));
                }
            }])
            ->orderBy('name')
            ->get();

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
                $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
                $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
                $period = CarbonPeriod::create($startDate, $endDate);

                $workdays = collect($period)->filter(function ($d) use ($holidays) {
                    return !$d->isWeekend() && !\App\Models\Calendar::isDateInHolidays($d, $holidays);
                });

                $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0;

                foreach ($workdays as $wDate) {
                    $dateString = $wDate->format('Y-m-d');
                    $attendanceRecord = $attendancesInMonth->firstWhere(function($item) use ($dateString) {
                        return Carbon::parse($item->attendance_time)->format('Y-m-d') === $dateString;
                    });
                    
                    $isSelfStudy = \App\Models\Calendar::isDateInSelfStudy($wDate, $selfStudyDays);
                    
                    if ($isSelfStudy) {
                        $hadir++;
                    } else {
                        $status = $attendanceRecord ? $attendanceRecord->status : null;
                        if (in_array($status, ['tepat_waktu', 'terlambat'])) $hadir++;
                        elseif ($status === 'sakit') $sakit++;
                        elseif ($status === 'izin') $izin++;
                        elseif ($status === 'alpa') $alpa++;
                    }
                }

                // Hitung persen berdasar effective_days - (alpa+izin+sakit) atau Hadir
                // User meminta jumlah efektif sebagai %
                $effDays = $trimesterMap[$m]['effective_days'];
                
                $totalAbsen = $sakit + $izin + $alpa;
                // Asumsi: JML kolom adalah total hari ia tidak hadir. % berdasar kehadiran
                $jml = $totalAbsen;
                if ($effDays > 0) {
                    // Bisa hadir / hari efektif * 100% atau (Hari Efektif - Jml) / Hari Efektif * 100%
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
        $perfectAttendanceCount = $reportData->filter(function($s) { return ($s->total_persen_num ?? 0) >= 100; })->count();
        $needsAttentionCount = $reportData->filter(function($s) { return ($s->total_persen_num ?? 0) < 85; })->count();

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

        // Ambil nama wali kelas jika ada
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
     * Membuat laporan detail kehadiran per siswa.
     */
    private function generateStudentDetailedReport(Request $request)
    {
        // MODIFIKASI: Tambahkan 'schoolClass.homeroomTeacher' untuk mengambil data wali kelas
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

        $holidays = \App\Models\Calendar::getHolidaysInRange($startDate, $endDate);
        $selfStudyDays = \App\Models\Calendar::getSelfStudyDaysInRange($startDate, $endDate);
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        
        $workdays = collect($period)->filter(function ($date) use ($holidays) {
            return !$date->isWeekend() && !\App\Models\Calendar::isDateInHolidays($date, $holidays);
        });

        $pdfData = $this->getCommonPdfData();
        $pdfData['student'] = $student;
        $pdfData['attendances'] = $attendances;
        $pdfData['workdays'] = $workdays;
        $pdfData['selfStudyDays'] = $selfStudyDays;
        $pdfData['startDate'] = $startDate->translatedFormat('d F Y');
        $pdfData['endDate'] = $endDate->translatedFormat('d F Y');

        // MODIFIKASI: Kirim nama WALI KELAS ke view PDF
        // Asumsi relasi di model SchoolClass bernama 'homeroomTeacher'
        // MODIFIKASI: Ambil nama DAN NIP wali kelas
        $homeroomTeacher = $student->schoolClass->homeroomTeacher;
        $pdfData['homeroomTeacherName'] = $homeroomTeacher->name ?? '-';
        $pdfData['homeroomTeacherNip'] = $homeroomTeacher->nip ?? null; // Ambil NIP

        $pdf = Pdf::loadView('admin.reports.student_pdf', $pdfData);
        return $pdf->stream('laporan-detail-' . $student->name . '.pdf');
    }

    /**
     * Membuat laporan rekap keterlambatan seluruh sekolah.
     */
    private function generateSchoolLatenessReport(Request $request)
    {
        // ... (Fungsi ini tidak berubah) ...
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
     * Membuat laporan siswa yang tidak absen pulang.
     */
    private function generateNoCheckoutReport(Request $request)
    {
        // ... (Fungsi ini tidak berubah) ...
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $attendancesQuery = Attendance::with(['student.schoolClass'])
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->whereNull('checkout_time')
            ->whereBetween('attendance_time', [$startDate, $endDate])
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

    /**
     * Mengambil data umum yang diperlukan untuk semua PDF.
     */
    private function getCommonPdfData()
    {
        $settings = Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            try {
                $logoData = Storage::disk('public')->get($logoPath);
                $logoBase64 = 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            }
            catch (\Exception $e) {
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
            // MODIFIKASI: Ambil nama dan NIP Kepala Sekolah dari pengaturan
            'headmasterName' => $settings->get('school_headmaster_name', '-'),
            'headmasterNip' => $settings->get('school_headmaster_nip', '-'),
        ];
    }
}