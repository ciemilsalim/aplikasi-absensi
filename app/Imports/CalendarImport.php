<?php

namespace App\Imports;

use App\Models\Calendar;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CalendarImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Calendar([
            'title' => $row['title'], // Judul Agenda
            'start_date' => $this->transformDate($row['start_date']),
            'end_date' => isset($row['end_date']) ? $this->transformDate($row['end_date']) : null,
            'description' => $row['description'] ?? null,
            'is_holiday' => isset($row['is_holiday']) ? filter_var($row['is_holiday'], FILTER_VALIDATE_BOOLEAN) : false,
        ]);
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        if (empty($value))
            return null;

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format($format);
            }
            return Carbon::parse($value)->format($format);
        }
        catch (\Exception $e) {
            return null;
        }
    }
}
