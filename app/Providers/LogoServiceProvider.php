<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class LogoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Mendaftarkan 'app_settings' sebagai singleton untuk efisiensi.
        // Logika untuk mengambil data dari database hanya akan dijalankan
        // satu kali, yaitu saat pertama kali dibutuhkan.
        $this->app->singleton('app_settings', function ($app) {
            // Mencegah query database saat menjalankan perintah console
            if ($app->runningInConsole()) {
                return collect();
            }
            
            try {
                // Ambil semua pengaturan dari database
                return Setting::pluck('value', 'key');
            } catch (QueryException $e) {
                // Jika database belum siap atau ada error lain, kembalikan koleksi kosong
                return collect();
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sekarang, di metode boot, kita hanya memberitahu semua view
        // untuk menggunakan singleton yang sudah kita daftarkan.
        View::composer('*', function ($view) {
            $settings = $this->app->make('app_settings');
            
            $view->with([
                'appLogoPath' => $settings->get('app_logo'),
                'appName' => $settings->get('school_name', config('app.name', 'AbsensiSiswa')),
                // PERBAIKAN: Mengirim status mode gelap sebagai boolean (true/false) ke semua halaman
                'darkModeEnabled' => $settings->get('dark_mode', 'off') === 'on',
            ]);
        });
    }
}
