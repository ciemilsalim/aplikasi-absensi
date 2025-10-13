<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceReportExport implements FromView, ShouldAutoSize, WithTitle, WithEvents
{
    protected $class;
    protected $students;
    protected $period;
    protected $attendances;
    protected $attendanceSummary;
    protected $selectedDate;

    /**
     * Konstruktor untuk menerima data yang SUDAH DIOLAH dari controller.
     */
    public function __construct($class, $students, $period, $attendances, $attendanceSummary, $selectedDate)
    {
        $this->class = $class;
        $this->students = $students;
        $this->period = $period;
        $this->attendances = $attendances;
        $this->attendanceSummary = $attendanceSummary;
        $this->selectedDate = $selectedDate;
    }

    /**
     * Mengembalikan view yang akan dirender menjadi Excel.
     * Menggunakan view 'teacher.exports.attendance-report' yang sudah Anda miliki.
     */
    public function view(): View
    {
        // Menggunakan view yang sudah ada dan mengirimkan semua data yang dibutuhkan
        return view('teacher.exports.attendance-report', [
            'class' => $this->class,
            'students' => $this->students,
            'period' => $this->period,
            'attendances' => $this->attendances,
            'attendanceSummary' => $this->attendanceSummary,
            'selectedDate' => $this->selectedDate,
            'settings' => [] // Tambahkan array kosong jika view membutuhkan variabel ini
        ]);
    }

    /**
     * Menentukan nama worksheet di file Excel.
     */
    public function title(): string
    {
        return 'Laporan Kehadiran ' . $this->selectedDate->format('M Y');
    }

    /**
     * Menambahkan styling setelah sheet dibuat.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $totalColumns = 2 + count($this->period) + 4;
                $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumns);

                $highestRow = $sheet->getHighestRow();

                // Merge Cells untuk Judul (Baris 1, 2, 3)
                $sheet->mergeCells('A1:'.$lastColumnLetter.'1');
                $sheet->mergeCells('A2:'.$lastColumnLetter.'2');
                
                $tableRange = 'A4:' . $lastColumnLetter . $highestRow;
                $headerRange = 'A4:' . $lastColumnLetter . '5';

                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($tableRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($tableRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B6:B'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Nama siswa rata kiri
                
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getColumnDimension('B')->setWidth(30);
            },
        ];
    }
}

