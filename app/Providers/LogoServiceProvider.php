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
        // Mendaftarkan 'app_settings' sebagai singleton untuk efisiensi
        $this->app->singleton('app_settings', function ($app) {
            if ($app->runningInConsole()) {
                return collect(); // Kembalikan koleksi kosong untuk perintah console
            }
            try {
                // Ambil semua pengaturan sekali saja
                return Setting::pluck('value', 'key');
            } catch (\Exception $e) {
                return collect(); // Kembalikan koleksi kosong jika ada error
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan data ke semua view menggunakan singleton yang sudah didaftarkan
        View::composer('*', function ($view) {
            $settings = $this->app->make('app_settings');
            
            $view->with([
                'appLogoPath' => $settings->get('app_logo'),
                // Kirim status mode gelap sebagai boolean (true/false)
                'darkModeEnabled' => $settings->get('dark_mode', 'off') === 'on',
            ]);
        });
    }
}
