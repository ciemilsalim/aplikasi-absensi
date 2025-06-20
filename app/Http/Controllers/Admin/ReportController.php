<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf; // Impor facade PDF
use Carbon\Carbon;

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
        
        // Ambil semua siswa di kelas tersebut beserta data kehadirannya
        $students = Student::where('school_class_id', $class->id)
                            ->with(['attendances' => function ($query) use ($date) {
                                $query->whereYear('attendance_time', $date->year)
                                      ->whereMonth('attendance_time', $date->month);
                            }])
                            ->orderBy('name')
                            ->get();

        // Siapkan data untuk view PDF
        $reportData = $students->map(function ($student) {
            return (object)[
                'name' => $student->name,
                'nis' => $student->nis,
                'hadir' => $student->attendances->count(),
                'tepat_waktu' => $student->attendances->where('status', 'tepat_waktu')->count(),
                'terlambat' => $student->attendances->where('status', 'terlambat')->count(),
            ];
        });

        // Buat PDF
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'reportData' => $reportData,
            'className' => $class->name,
            'monthName' => $monthName,
        ]);

        // Tampilkan PDF di browser
        return $pdf->stream('laporan-kehadiran-' . $class->name . '-' . $date->format('F-Y') . '.pdf');
    }
}
