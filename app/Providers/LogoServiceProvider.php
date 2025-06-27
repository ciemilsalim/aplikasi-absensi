<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;

class LogoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('app_settings', function ($app) {
            if ($app->runningInConsole()) { return collect(); }
            try { return Setting::pluck('value', 'key'); } 
            catch (QueryException $e) { return collect(); }
        });
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $settings = $this->app->make('app_settings');
            $user = Auth::user();
            
            $pendingLeaveRequestsCount = 0;
            $teacherPendingLeaveRequestsCount = 0;

            if (Auth::check()) {
                try {
                    // Notifikasi untuk Admin
                    if ($user->role === 'admin') {
                        $pendingLeaveRequestsCount = LeaveRequest::where('status', 'pending')->count();
                    }
                    // Notifikasi untuk Guru Wali Kelas
                    if ($user->role === 'teacher' && $user->teacher?->homeroomClass) {
                        $studentIds = $user->teacher->homeroomClass->students()->pluck('id');
                        $teacherPendingLeaveRequestsCount = LeaveRequest::whereIn('student_id', $studentIds)->where('status', 'pending')->count();
                    }
                } catch (QueryException $e) {
                    // Biarkan 0 jika ada masalah database
                }
            }
            
            $view->with([
                'appLogoPath' => $settings->get('app_logo'),
                'appName' => $settings->get('school_name', config('app.name', 'AbsensiSiswa')),
                'darkModeEnabled' => $settings->get('dark_mode', 'off') === 'on',
                'pendingLeaveRequestsCount' => $pendingLeaveRequestsCount, // Untuk Admin
                'teacherPendingLeaveRequestsCount' => $teacherPendingLeaveRequestsCount, // Untuk Guru
            ]);
        });
    }
}
