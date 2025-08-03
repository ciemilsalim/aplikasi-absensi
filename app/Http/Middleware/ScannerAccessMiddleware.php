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
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // PERBAIKAN: Izinkan jika peran adalah admin, operator, atau guru (tanpa syarat wali kelas)
        if (in_array($user->role, ['admin', 'operator', 'teacher'])) {
            return $next($request);
        }

        // Jika tidak memenuhi syarat, kembalikan ke dasbor dengan pesan error
        return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses ke halaman pemindai.');
    }
}
