<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;

class LogoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // **PERBAIKAN UTAMA ADA DI SINI**
        // Kita mendaftarkan 'appLogoPath' sebagai singleton.
        // Logika untuk mengambil data dari database hanya akan dijalankan
        // satu kali, yaitu saat 'appLogoPath' pertama kali dibutuhkan.
        $this->app->singleton('appLogoPath', function ($app) {
            // Mencegah query database saat menjalankan perintah console
            if ($app->runningInConsole()) {
                return null;
            }
            
            try {
                $logo = Setting::where('key', 'app_logo')->first();
                return $logo ? $logo->value : null;
            } catch (\Exception $e) {
                // Jika database belum siap atau ada error lain, kembalikan null
                return null;
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
        // Ini lebih aman karena tidak ada query database yang terjadi di sini.
        View::composer('*', function ($view) {
            $view->with('appLogoPath', $this->app->make('appLogoPath'));
        });
    }
}
