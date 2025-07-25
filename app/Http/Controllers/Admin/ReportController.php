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
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;

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
     * Membuat dan menampilkan laporan dalam format PDF berdasarkan jenisnya.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:class_monthly,student_detailed,school_lateness',
            'month' => 'required_if:report_type,class_monthly|date_format:Y-m',
            'school_class_id' => 'required_if:report_type,class_monthly|exists:school_classes,id',
            'student_id' => 'required_if:report_type,student_detailed|exists:students,id',
            'start_date' => 'required_if:report_type,student_detailed,school_lateness|date',
            'end_date' => 'required_if:report_type,student_detailed,school_lateness|date|after_or_equal:start_date',
        ]);

        $reportType = $request->report_type;

        if ($reportType === 'class_monthly') {
            return $this->generateClassMonthlyReport($request);
        } elseif ($reportType === 'student_detailed') {
            return $this->generateStudentDetailedReport($request);
        } elseif ($reportType === 'school_lateness') {
            return $this->generateSchoolLatenessReport($request);
        }

        return redirect()->back()->with('error', 'Jenis laporan tidak valid.');
    }

    /**
     * Membuat laporan rekap kehadiran bulanan per kelas.
     */
    private function generateClassMonthlyReport(Request $request)
    {
        $class = SchoolClass::findOrFail($request->school_class_id);
        $date = Carbon::createFromFormat('Y-m', $request->month);
        $monthName = $date->translatedFormat('F Y');
        
        $students = Student::where('school_class_id', $class->id)
                            ->with(['attendances' => function ($query) use ($date) {
                                $query->whereYear('attendance_time', $date->year)
                                      ->whereMonth('attendance_time', $date->month);
                            }])
                            ->orderBy('name')
                            ->get();

        $reportData = $students->map(function ($student) {
            $attendancesInMonth = $student->attendances;
            $tepatWaktu = $attendancesInMonth->where('status', 'tepat_waktu')->count();
            $terlambat = $attendancesInMonth->where('status', 'terlambat')->count();
            $hadir = $tepatWaktu + $terlambat;
            $sakit = $attendancesInMonth->where('status', 'sakit')->count();
            $izin = $attendancesInMonth->where('status', 'izin')->count();
            $alpa = $attendancesInMonth->where('status', 'alpa')->count();

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
     * Membuat laporan detail kehadiran per siswa.
     */
    private function generateStudentDetailedReport(Request $request)
    {
        $student = Student::with('schoolClass')->findOrFail($request->student_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $attendances = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->orderBy('attendance_time', 'asc')
            ->get();

        $pdfData = $this->getCommonPdfData();
        $pdfData['student'] = $student;
        $pdfData['attendances'] = $attendances;
        $pdfData['startDate'] = $startDate->translatedFormat('d F Y');
        $pdfData['endDate'] = $endDate->translatedFormat('d F Y');

        $pdf = Pdf::loadView('admin.reports.student_pdf', $pdfData);
        return $pdf->stream('laporan-detail-' . $student->name . '.pdf');
    }

    /**
     * Membuat laporan rekap keterlambatan seluruh sekolah.
     */
    private function generateSchoolLatenessReport(Request $request)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $latenessData = Student::with('schoolClass')
            ->whereHas('attendances', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'terlambat')
                      ->whereBetween('attendance_time', [$startDate, $endDate]);
            })
            ->withCount(['attendances as late_count' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'terlambat')
                      ->whereBetween('attendance_time', [$startDate, $endDate]);
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
     * Mengambil data umum yang diperlukan untuk semua PDF (logo, nama sekolah).
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
            } catch (\Exception $e) {
                $logoBase64 = null;
            }
        }
        
        return [
            'schoolName' => $settings->get('school_name', config('app.name')),
            'schoolAddress' => $settings->get('school_address'),
            'logoBase64' => $logoBase64,
        ];
    }
}
