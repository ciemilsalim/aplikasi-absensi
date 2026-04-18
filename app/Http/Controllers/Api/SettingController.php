<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get GPS settings for attendance.
     */
    public function getGpsSettings()
    {
        $settings = Setting::whereIn('key', ['school_latitude', 'school_longitude', 'attendance_radius'])
            ->pluck('value', 'key');

        return response()->json([
            'status' => 'success',
            'data' => [
                'latitude' => $settings->get('school_latitude'),
                'longitude' => $settings->get('school_longitude'),
                'radius' => (int) $settings->get('attendance_radius', 100),
            ]
        ]);
    }

    /**
     * Get school profile information and logo.
     */
    public function getSchoolProfile()
    {
        $settings = Setting::whereIn('key', [
            'school_name', 
            'school_address', 
            'school_headmaster_name', 
            'school_headmaster_nip', 
            'app_logo'
        ])->pluck('value', 'key');

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $settings->get('school_name', 'SIASEK-Absensi'),
                'address' => $settings->get('school_address'),
                'headmaster_name' => $settings->get('school_headmaster_name'),
                'headmaster_nip' => $settings->get('school_headmaster_nip'),
                'logo_url' => $settings->get('app_logo') ? asset('storage/' . $settings->get('app_logo')) : null,
            ]
        ]);
    }
}
