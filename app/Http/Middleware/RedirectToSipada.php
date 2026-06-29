<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToSipada
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return redirect()->route('admin.dashboard')->with('error', 'Halaman ini telah dinonaktifkan di Aplikasi Absensi. Silakan kelola data ini melalui portal Sistem Pangkalan Data (SIPADA).');
    }
}
