<?php

namespace App\Exports;

use App\Models\Setting;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;

class AttendanceReportExport implements FromView, WithEvents
{
    protected $selectedDate;

    public function __construct(Carbon $selectedDate)
    {
        $this->selectedDate = $selectedDate;
    }

    /**
     * Mengembalikan view yang akan dirender menjadi Excel.
     */
    public function view(): View
    {
        $teacher = Auth::user()->teacher;
        $class = $teacher->homeroomClass;
        $students = Student::where('school_class_id', $class->id)->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $startDate = $this->selectedDate->copy()->startOfMonth();
        $endDate = $this->selectedDate->copy()->endOfMonth();

        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('attendance_time', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentAttendances) {
                return $studentAttendances->keyBy(function ($item) {
                    return Carbon::parse($item->attendance_time)->format('Y-m-d');
                });
            });

        $period = CarbonPeriod::create($startDate, $endDate);

        // Filter untuk mengecualikan akhir pekan (Sabtu & Minggu)
        $period = collect($period)->filter(function ($date) {
            return !$date->isWeekend();
        });

        $settings = Setting::all()->pluck('value', 'key');
        
        return view('teacher.exports.attendance-report', [
            'settings' => $settings,
            'class' => $class,
            'students' => $students,
            'attendances' => $attendances,
            'period' => $period,
            'selectedDate' => $this->selectedDate
        ]);
    }

    /**
     * Menambahkan styling setelah sheet dibuat.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Mengambil jumlah baris dan kolom
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Mendefinisikan range tabel secara lebih spesifik (mulai dari A9 - header tabel)
                $tableHeaderRow = 9;
                $tableRange = 'A' . $tableHeaderRow . ':' . $highestColumn . $highestRow;

                // Menambahkan border HANYA ke sel tabel riwayat kehadiran
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Mengatur alignment vertikal untuk seluruh sheet
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Mengatur alignment horizontal untuk tabel
                $sheet->getStyle($tableRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . ($tableHeaderRow + 1) . ':B'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Nama siswa rata kiri
                
                // Mengatur header tabel (baris ke-9) agar bold
                $sheet->getStyle('A' . $tableHeaderRow . ':' . $highestColumn . $tableHeaderRow)->getFont()->setBold(true);

                // Auto-size kolom agar pas
                foreach (range('A', $highestColumn) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
                // Atur ulang kolom nama siswa agar sedikit lebih lebar
                $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(30);


                // Memberikan padding pada nama siswa
                $sheet->getStyle('B' . ($tableHeaderRow + 1) . ':B'.$highestRow)->getAlignment()->setIndent(1);

                // Merge cell untuk kop dan judul dokumen
                if ($lastColumnIndex > 1) { // Hanya merge jika lebih dari 1 kolom
                    $sheet->mergeCells('A1:'.$highestColumn.'1'); // Nama Sekolah
                    $sheet->mergeCells('A2:'.$highestColumn.'2'); // Alamat
                    $sheet->mergeCells('A4:'.$highestColumn.'4'); // Garis pemisah
                    $sheet->mergeCells('A6:'.$highestColumn.'6'); // Judul "LAPORAN KEHADIRAN SISWA"
                    $sheet->mergeCells('A7:'.$highestColumn.'7'); // Judul "KELAS: 7A - BULAN: Agustus 2025"
                }

                // Styling untuk kop dan judul
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A6')->getFont()->setBold(true)->setUnderline(true);
                $sheet->getStyle('A1:A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Menambahkan garis bawah tebal sebagai pemisah
                $sheet->getStyle('A4:' . $highestColumn . '4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);

            },
        ];
    }
}

