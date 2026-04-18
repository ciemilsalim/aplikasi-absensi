<?php

namespace App\Traits;

use App\Models\Setting;

trait GpsValidationTrait
{
    /**
     * Calculate distance between two points (Haversine formula).
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validate if coordinates are within school radius.
     */
    public function validateGps($latitude, $longitude)
    {
        $schoolLat = Setting::where('key', 'school_latitude')->value('value');
        $schoolLng = Setting::where('key', 'school_longitude')->value('value');
        $radius = Setting::where('key', 'attendance_radius')->value('value') ?? 100;

        if ($schoolLat && $schoolLng && $radius) {
            $distance = $this->calculateDistance($latitude, $longitude, $schoolLat, $schoolLng);
            if ($distance > $radius) {
                return [
                    'isValid' => false,
                    'distance' => round($distance),
                    'radius' => (int)$radius,
                    'message' => 'Anda berada di luar jangkauan sekolah. Jarak: ' . round($distance) . 'm'
                ];
            }
        }

        return ['isValid' => true];
    }
}
