<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UpdateLastSeenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika pengguna sudah login, perbarui timestamp last_seen_at mereka
        if (Auth::check()) {
            User::where('id', Auth::id())->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
