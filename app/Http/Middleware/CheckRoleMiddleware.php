<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Jika pengguna tidak login atau tidak memiliki salah satu dari peran yang diizinkan,
        // hentikan permintaan dan tampilkan halaman error 403 (Forbidden).
        if (!Auth::check() || !in_array(Auth::user()->role, $roles)) {
            abort(403, 'AKSES DITOLAK');
        }

        return $next($request);
    }
}
