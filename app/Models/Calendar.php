<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'description',
        'is_holiday',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_holiday' => 'boolean',
    ];

    /**
     * Mendapatkan daftar hari libur dalam rentang tanggal tertentu
     */
    public static function getHolidaysInRange($startDate, $endDate)
    {
        return self::where('is_holiday', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereDate('start_date', '<=', $startDate)
                            ->whereDate('end_date', '>=', $endDate);
                    });
            })->get();
    }

    /**
     * Memeriksa apakah sebuah instance Carbon jatuh pada hari libur di koleksi Calendar
     */
    public static function isDateInHolidays($date, $holidays)
    {
        $checkDate = $date->copy()->startOfDay();
        foreach ($holidays as $holiday) {
            $start = $holiday->start_date->copy()->startOfDay();
            $end = $holiday->end_date ? $holiday->end_date->copy()->endOfDay() : $start->copy()->endOfDay();
            if ($checkDate->between($start, $end)) {
                return true;
            }
        }
        return false;
    }
}
