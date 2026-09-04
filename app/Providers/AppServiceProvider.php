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
                $semesters = \App\Models\Semester::with('academicYear')->get()->sortBy([
                    function ($a, $b) {
                        $yearA = $a->academicYear?->name ?? '';
                        $yearB = $b->academicYear?->name ?? '';
                        preg_match('/(\d{4})/', $yearA, $mA);
                        preg_match('/(\d{4})/', $yearB, $mB);
                        $startA = isset($mA[1]) ? (int)$mA[1] : 0;
                        $startB = isset($mB[1]) ? (int)$mB[1] : 0;
                        if ($startA !== $startB) {
                            return $startA <=> $startB;
                        }
                        return strcmp($yearA, $yearB);
                    },
                    function ($a, $b) {
                        $sa = str_contains(strtolower($a->name ?? ''), 'ganjil') ? 1 : (str_contains(strtolower($a->name ?? ''), 'genap') ? 2 : 3);
                        $sb = str_contains(strtolower($b->name ?? ''), 'ganjil') ? 1 : (str_contains(strtolower($b->name ?? ''), 'genap') ? 2 : 3);
                        return $sa <=> $sb;
                    }
                ])->values();

                $activeSemesterId = session('active_semester_id');
                $view->with('globalSemesters', $semesters)
                     ->with('globalActiveSemesterId', $activeSemesterId);
            }
        });
    }
}
