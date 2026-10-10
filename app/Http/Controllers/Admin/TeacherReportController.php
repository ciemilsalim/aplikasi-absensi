<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminReportService;
use Illuminate\Http\Request;

class TeacherReportController extends Controller
{
    public function __construct(
        protected AdminReportService $service
    ) {}

    /**
     * Menampilkan antarmuka dasbor laporan presensi guru.
     */
    public function index(Request $request)
    {
        $month = (int)$request->input('month', date('m'));
        $year = (int)$request->input('year', date('Y'));
        $activeSemesterId = session('active_semester_id');

        $data = $this->service->getTeacherAttendanceReportData($month, $year, $activeSemesterId);

        return view('admin.reports.teacher.index', $data);
    }

    /**
     * Menghasilkan dokumen cetak PDF rekapitulasi presensi guru bulanan.
     */
    public function print(Request $request)
    {
        $month = (int)$request->input('month', date('m'));
        $year = (int)$request->input('year', date('Y'));
        $activeSemesterId = session('active_semester_id');

        return $this->service->generateTeacherAttendancePdfReport($month, $year, $activeSemesterId);
    }
}
