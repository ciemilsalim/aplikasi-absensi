<?php

namespace App\Traits;

use App\Models\Setting;
use Carbon\Carbon;

trait GpsValidationTrait
{
    /**
     * Calculate distance between two points in meters (Haversine formula).
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2): float
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
     * Validate GPS coordinates, signal accuracy, mock location flags, and geofence radius.
     */
    public function validateGps(
        float|int|string $latitude,
        float|int|string $longitude,
        float|int|string|null $accuracy = null,
        bool|int|string|null $isMock = null,
        int|string|null $clientTimestamp = null
    ): array {
        // 1. Anti-Fake GPS & Mock Location Provider Detection
        $mockDetected = filter_var($isMock, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true
            || in_array((string)$isMock, ['1', 'true', 'mock'], true);

        if ($mockDetected) {
            return [
                'isValid' => false,
                'reason' => 'mock_location',
                'message' => 'Terdeteksi penggunaan Fake GPS / Mock Location. Harap nonaktifkan aplikasi lokasi palsu.',
            ];
        }

        // 2. Hardware GPS Accuracy Validation
        if ($accuracy !== null && $accuracy !== '') {
            $accVal = (float) $accuracy;
            $maxAccuracy = (float) (Setting::where('key', 'max_gps_accuracy')->value('value') ?? 100);

            if ($accVal <= 0) {
                return [
                    'isValid' => false,
                    'reason' => 'invalid_accuracy',
                    'message' => 'Nilai akurasi GPS tidak valid.',
                ];
            }

            if ($accVal > $maxAccuracy) {
                return [
                    'isValid' => false,
                    'reason' => 'inaccurate_gps',
                    'accuracy' => round($accVal, 1),
                    'max_accuracy' => (int) $maxAccuracy,
                    'message' => 'Sinyal GPS tidak akurat (±' . round($accVal) . 'm, batas maksimal ' . (int) $maxAccuracy . 'm). Pastikan GPS berada pada mode akurasi tinggi.',
                ];
            }
        }

        // 3. Client Hardware Timestamp Drift Validation (Max 5 minutes)
        if ($clientTimestamp) {
            try {
                $clientTime = is_numeric($clientTimestamp)
                    ? (strlen((string) $clientTimestamp) >= 13
                        ? Carbon::createFromTimestampMs((int) $clientTimestamp)
                        : Carbon::createFromTimestamp((int) $clientTimestamp))
                    : Carbon::parse($clientTimestamp);

                $driftSeconds = abs(now()->diffInSeconds($clientTime));
                if ($driftSeconds > 300) {
                    return [
                        'isValid' => false,
                        'reason' => 'time_tampered',
                        'drift_seconds' => $driftSeconds,
                        'message' => 'Waktu pada perangkat tidak sesuai dengan waktu server (selisih ' . round($driftSeconds / 60) . ' menit). Pastikan jam perangkat diatur otomatis.',
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore parse failures gracefully and proceed to radius check
            }
        }

        // 4. Geofence Radius Validation (Haversine Distance)
        $schoolLat = Setting::where('key', 'school_latitude')->value('value');
        $schoolLng = Setting::where('key', 'school_longitude')->value('value');
        $radius = Setting::where('key', 'attendance_radius')->value('value') ?? 100;

        if ($schoolLat && $schoolLng && $radius) {
            $distance = $this->calculateDistance((float)$latitude, (float)$longitude, (float)$schoolLat, (float)$schoolLng);
            if ($distance > (float)$radius) {
                return [
                    'isValid' => false,
                    'reason' => 'out_of_radius',
                    'distance' => round($distance),
                    'radius' => (int)$radius,
                    'message' => 'Anda berada di luar jangkauan sekolah. Jarak: ' . round($distance) . 'm (Radius: ' . (int)$radius . 'm)',
                ];
            }

            return [
                'isValid' => true,
                'distance' => round($distance),
                'radius' => (int)$radius,
            ];
        }

        return ['isValid' => true];
    }
}
