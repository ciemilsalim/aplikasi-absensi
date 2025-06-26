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
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Mendaftarkan 'app_settings' sebagai singleton untuk efisiensi.
        // Logika ini hanya akan dijalankan sekali per permintaan.
        $this->app->singleton('app_settings', function ($app) {
            // Mencegah query database saat menjalankan perintah console
            if ($app->runningInConsole()) {
                return collect();
            }
            
            try {
                // Ambil semua pengaturan dari database
                return Setting::pluck('value', 'key');
            } catch (QueryException $e) {
                // Jika database belum siap, kembalikan koleksi kosong
                return collect();
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan data ke semua view menggunakan singleton yang sudah didaftarkan.
        View::composer('*', function ($view) {
            $settings = $this->app->make('app_settings');
            
            $pendingLeaveRequestsCount = 0;
            // Hanya jalankan query jika pengguna adalah admin yang sudah login
            if (Auth::check() && Auth::user()->role === 'admin') {
                try {
                    $pendingLeaveRequestsCount = LeaveRequest::where('status', 'pending')->count();
                } catch (QueryException $e) {
                    $pendingLeaveRequestsCount = 0;
                }
            }
            
            // Mengirim semua data yang diperlukan ke semua view
            $view->with([
                'appLogoPath' => $settings->get('app_logo'),
                'appName' => $settings->get('school_name', config('app.name', 'AbsensiSiswa')),
                'darkModeEnabled' => $settings->get('dark_mode', 'off') === 'on',
                'pendingLeaveRequestsCount' => $pendingLeaveRequestsCount,
            ]);
        });
    }
}
