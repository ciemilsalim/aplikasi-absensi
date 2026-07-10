<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('semesters')) {
                $semesters = \App\Models\Semester::with('academicYear')->orderBy('id', 'desc')->get();
                $activeSemesterId = session('active_semester_id');
                $view->with('globalSemesters', $semesters)
                     ->with('globalActiveSemesterId', $activeSemesterId);
            }
        });
    }
}
