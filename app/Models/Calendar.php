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
        'is_self_study',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_holiday' => 'boolean',
        'is_self_study' => 'boolean',
    ];

    /**
     * Mendapatkan daftar hari libur dalam rentang tanggal tertentu (dengan caching)
     */
    public static function getHolidaysInRange($startDate, $endDate)
    {
        $startStr = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
        $endStr = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
        $cacheKey = "calendar:holidays:{$startStr}:{$endStr}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return self::where('is_holiday', true)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereDate('start_date', '<=', $startDate)
                                ->whereDate('end_date', '>=', $endDate);
                        });
                })->get();
        });
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

    /**
     * Mendapatkan daftar hari belajar mandiri dalam rentang tanggal tertentu (dengan caching)
     */
    public static function getSelfStudyDaysInRange($startDate, $endDate)
    {
        $startStr = \Carbon\Carbon::parse($startDate)->format('Y-m-d');
        $endStr = \Carbon\Carbon::parse($endDate)->format('Y-m-d');
        $cacheKey = "calendar:self_study:{$startStr}:{$endStr}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return self::where('is_self_study', true)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereDate('start_date', '<=', $startDate)
                                ->whereDate('end_date', '>=', $endDate);
                        });
                })->get();
        });
    }

    /**
     * Memeriksa apakah sebuah instance Carbon jatuh pada hari belajar mandiri
     */
    public static function isDateInSelfStudy($date, $selfStudyDays)
    {
        return self::isDateInHolidays($date, $selfStudyDays);
    }
}
