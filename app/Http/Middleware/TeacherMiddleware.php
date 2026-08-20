<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (
            auth()->user()->hasRole('teacher') || 
            auth()->user()->hasAnyRole(['admin', 'operator', 'kepala_sekolah', 'kepala sekolah', 'headmaster'])
        )) {
            return $next($request);
        }
        return redirect('/');
    }
}