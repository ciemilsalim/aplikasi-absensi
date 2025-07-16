<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // PERBAIKAN: Menambahkan middleware global dan alias
        
        // Middleware ini akan berjalan pada setiap permintaan web
        $middleware->web(append: [
            \App\Http\Middleware\UpdateLastSeenMiddleware::class,
        ]);

        // Mendaftarkan alias untuk middleware peran
        $middleware->alias([
            'admin'          => \App\Http\Middleware\AdminMiddleware::class,
            'parent'         => \App\Http\Middleware\ParentMiddleware::class,
            'teacher'        => \App\Http\Middleware\TeacherMiddleware::class,
            'scanner.access' => \App\Http\Middleware\ScannerAccessMiddleware::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        // PERBAIKAN: Menjadwalkan perintah untuk mengecek siswa yang alpa
        $schedule->command('attendance:check-absent')->weekdays()->dailyAt('01:20')
            ->description('Cek siswa yang alpa setiap hari kerja pada pukul 01:20');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
