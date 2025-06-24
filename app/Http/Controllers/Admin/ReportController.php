<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Menampilkan form untuk memilih parameter laporan.
     */
    public function create()
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.reports.create', compact('classes'));
    }

    /**
     * Membuat dan menampilkan laporan dalam format PDF.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'month' => 'required|date_format:Y-m',
        ]);

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

        // Hitung statistik detail untuk setiap siswa
        $reportData = $students->map(function ($student) use ($date) {
            $attendancesInMonth = $student->attendances;

            $tepatWaktu = $attendancesInMonth->where('status', 'tepat_waktu')->count();
            $terlambat = $attendancesInMonth->where('status', 'terlambat')->count();
            $hadir = $tepatWaktu + $terlambat;
            $sakit = $attendancesInMonth->where('status', 'sakit')->count();
            $izin = $attendancesInMonth->where('status', 'izin')->count();

            // Hitung hari kerja dalam sebulan (asumsi Senin-Sabtu)
            $workDays = 0;
            for ($day = 1; $day <= $date->daysInMonth; $day++) {
                if (!$date->copy()->setDay($day)->isSunday()) {
                    $workDays++;
                }
            }
            $totalRecords = $attendancesInMonth->count();
            $alpa = $workDays - $totalRecords;

            return (object)[
                'name' => $student->name,
                'nis' => $student->nis,
                'hadir' => $hadir,
                'tepat_waktu' => $tepatWaktu,
                'terlambat' => $terlambat,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa > 0 ? $alpa : 0,
            ];
        });

        // Ambil data sekolah dari pengaturan
        $settings = Setting::pluck('value', 'key');
        $schoolName = $settings->get('school_name', config('app.name'));
        $schoolAddress = $settings->get('school_address');
        $logoPath = $settings->get('app_logo');
        
        // Konversi logo ke base64 agar bisa disematkan di PDF
        $logoBase64 = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoData = Storage::disk('public')->get($logoPath);
            $logoBase64 = 'data:image/' . pathinfo(storage_path('app/public/' . $logoPath), PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
        }

        // Buat PDF
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'reportData' => $reportData,
            'className' => $class->name,
            'monthName' => $monthName,
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
        ]);

        return $pdf->stream('laporan-kehadiran-' . $class->name . '-' . $date->format('F-Y') . '.pdf');
    }
}
