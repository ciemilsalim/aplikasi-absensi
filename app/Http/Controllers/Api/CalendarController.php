<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Calendar;

class CalendarController extends Controller
{
    /**
     * Get all calendar events.
     */
    public function index()
    {
        $events = Calendar::orderBy('start_date')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date->format('Y-m-d'),
                    'end_date' => $event->end_date ? $event->end_date->format('Y-m-d') : $event->start_date->format('Y-m-d'),
                    'description' => $event->description,
                    'is_holiday' => $event->is_holiday,
                    'is_self_study' => $event->is_self_study,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $events
        ]);
    }
}
