<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ScannerAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan pengguna sudah login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Izinkan jika peran adalah admin
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Izinkan jika peran adalah guru DAN merupakan wali kelas
        if ($user->role === 'teacher' && $user->teacher?->homeroomClass) {
            return $next($request);
        }

        // Jika tidak memenuhi syarat, kembalikan ke dasbor dengan pesan error
        return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses ke halaman pemindai.');
    }
}
