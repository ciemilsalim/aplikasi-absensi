<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // 1. Statistik Hari Ini
        $today = Carbon::today();
        $totalTeachers = Teacher::count();
        $presentToday = TeacherAttendance::whereDate('created_at', $today)
            ->whereIn('status', ['tepat_waktu', 'terlambat'])
            ->count();
        $lateToday = TeacherAttendance::whereDate('created_at', $today)
            ->where('status', 'terlambat')
            ->count();
        $absentToday = TeacherAttendance::whereDate('created_at', $today)
            ->where('status', 'alpa') // Or check logic for 'not present'
            ->count();
        $leaveToday = TeacherAttendance::whereDate('created_at', $today)
            ->whereIn('status', ['izin', 'sakit'])
            ->count();

        // 2. Chart Data (Bulanan)
        $attendanceTrend = TeacherAttendance::select(DB::raw('DATE(created_at) as date'), 'status', DB::raw('count(*) as count'))
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('date', 'status')
            ->get();

        $dates = [];
        $dataPresent = [];
        $dataLate = [];
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::createFromDate($year, $month, $i)->format('Y-m-d');
            $dates[] = $i; // Just the day number
            $dataPresent[] = $attendanceTrend->where('date', $date)->whereIn('status', ['tepat_waktu', 'terlambat'])->sum('count');
            $dataLate[] = $attendanceTrend->where('date', $date)->where('status', 'terlambat')->sum('count');
        }

        // 3. Rekapitulasi per Guru
        $teachers = Teacher::with(['attendances' => function ($q) use ($month, $year) {
            $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }])->orderBy('name')->get();

        $recap = $teachers->map(function ($teacher) {
            return [
            'name' => $teacher->name,
            'nip' => $teacher->nip,
            'hadir' => $teacher->attendances->whereIn('status', ['tepat_waktu', 'terlambat'])->count(),
            'terlambat' => $teacher->attendances->where('status', 'terlambat')->count(),
            'sakit' => $teacher->attendances->where('status', 'sakit')->count(),
            'izin' => $teacher->attendances->where('status', 'izin')->count(),
            'alpa' => $teacher->attendances->where('status', 'alpa')->count(),
            ];
        });

        return view('admin.reports.teacher.index', compact(
            'totalTeachers', 'presentToday', 'lateToday', 'absentToday', 'leaveToday',
            'dates', 'dataPresent', 'dataLate',
            'recap', 'month', 'year'
        ));
    }

    public function print(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $monthName = Carbon::createFromDate($year, $month)->translatedFormat('F Y');

        // Rekapitulasi per Guru (Logic copied for PDF generation)
        $teachers = Teacher::with(['attendances' => function ($q) use ($month, $year) {
            $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }])->orderBy('name')->get();

        $recap = $teachers->map(function ($teacher) {
            return [
            'name' => $teacher->name,
            'nip' => $teacher->nip,
            'hadir' => $teacher->attendances->whereIn('status', ['tepat_waktu', 'terlambat'])->count(),
            'terlambat' => $teacher->attendances->where('status', 'terlambat')->count(),
            'sakit' => $teacher->attendances->where('status', 'sakit')->count(),
            'izin' => $teacher->attendances->where('status', 'izin')->count(),
            'alpa' => $teacher->attendances->where('status', 'alpa')->count(),
            ];
        });

        $dates = [];
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dates[] = $i;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.teacher.pdf', array_merge($this->getCommonPdfData(), compact('recap', 'monthName', 'month', 'year', 'dates')));
        return $pdf->stream('laporan-absensi-guru-' . $monthName . '.pdf');
    }

    private function getCommonPdfData()
    {
        $settings = \App\Models\Setting::pluck('value', 'key');
        $logoPath = $settings->get('app_logo');
        $logoBase64 = null;
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            try {
                $logoData = \Illuminate\Support\Facades\Storage::disk('public')->get($logoPath);
                $logoBase64 = 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
            }
            catch (\Exception $e) {
                $logoBase64 = null;
            }
        }

        return [
            'schoolName' => $settings->get('school_name', config('app.name')),
            'schoolAddress' => $settings->get('school_address'),
            'logoBase64' => $logoBase64,
            'headmasterName' => $settings->get('school_headmaster_name', '-'),
            'headmasterNip' => $settings->get('school_headmaster_nip', '-'),
        ];
    }
}
