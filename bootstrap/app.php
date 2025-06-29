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
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'parent' => \App\Http\Middleware\ParentMiddleware::class, // Tambahkan ini
            'teacher' => \App\Http\Middleware\TeacherMiddleware::class, // Tambahkan ini
            'scanner.access' => \App\Http\Middleware\ScannerAccessMiddleware::class, // Tambahkan ini
            
        ]);
        $middleware->web(append: [
                \App\Http\Middleware\UpdateLastSeenMiddleware::class, // Tambahkan ini
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
