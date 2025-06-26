<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Models\LeaveRequest; // Impor model LeaveRequest
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
            
            // Ambil jumlah pengajuan yang pending hanya jika admin login
            $pendingLeaveRequestsCount = 0;
            if (Auth::check() && Auth::user()->role === 'admin') {
                try {
                    $pendingLeaveRequestsCount = LeaveRequest::where('status', 'pending')->count();
                } catch (QueryException $e) {
                    // Biarkan 0 jika ada masalah database
                }
            }
            
            $view->with([
                'appLogoPath' => $settings->get('app_logo'),
                'appName' => $settings->get('school_name', config('app.name', 'AbsensiSiswa')),
                'darkModeEnabled' => $settings->get('dark_mode', 'off') === 'on',
                'pendingLeaveRequestsCount' => $pendingLeaveRequestsCount, // Kirim data notifikasi
            ]);
        });
    }
}
