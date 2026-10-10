<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected AdminReportService $service
    ) {}

    /**
     * Menampilkan form untuk memilih parameter laporan.
     */
    public function create()
    {
        $data = $this->service->getReportFormOptions();

        return view('admin.reports.create', $data);
    }

    /**
     * Menampilkan antarmuka Analitik Visual (Chart).
     */
    public function charts()
    {
        $data = $this->service->getChartFormOptions();

        return view('admin.reports.charts', $data);
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

        $data = $this->service->getChartAnalyticsData($params);

        return response()->json($data);
    }

    /**
     * Membuat dan menampilkan laporan dalam format PDF berdasarkan jenisnya.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:class_monthly,class_trimester,student_detailed,school_lateness,school_no_checkout,cocurricular_monthly',
            'month' => 'required_if:report_type,class_monthly,cocurricular_monthly|date_format:Y-m',
            'school_class_id' => 'required_if:report_type,class_monthly,class_trimester,cocurricular_monthly|exists:school_classes,id',
            'cocurricular_id' => 'required_if:report_type,cocurricular_monthly|exists:cocurriculars,id',
            'trimester' => 'required_if:report_type,class_trimester|in:1,2,3,4',
            'year' => 'required_if:report_type,class_trimester|integer|min:2000',
            'student_id' => 'required_if:report_type,student_detailed|exists:students,id',
            'start_date' => 'required_if:report_type,student_detailed,school_lateness,school_no_checkout|date',
            'end_date' => 'required_if:report_type,student_detailed,school_lateness,school_no_checkout|date|after_or_equal:start_date',
        ]);

        return match ($request->report_type) {
            'class_monthly' => $this->service->generateClassMonthlyReport($request),
            'cocurricular_monthly' => $this->service->generateCocurricularMonthlyReport($request),
            'class_trimester' => $this->service->generateClassTrimesterReport($request),
            'student_detailed' => $this->service->generateStudentDetailedReport($request),
            'school_lateness' => $this->service->generateSchoolLatenessReport($request),
            'school_no_checkout' => $this->service->generateNoCheckoutReport($request),
            default => redirect()->back()->with('error', 'Jenis laporan tidak valid.'),
        };
    }
}