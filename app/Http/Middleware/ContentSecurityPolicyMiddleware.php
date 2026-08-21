<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicyMiddleware
{
    /**
     * Handle an incoming request and set permissive/functional CSP headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Define comprehensive CSP allowing Tailwind CDN, AlpineJS, Google Fonts, Chart.js, camera data, and websockets
        $csp = "default-src 'self' https: http: data: blob: 'unsafe-inline' 'unsafe-eval'; "
             . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://unpkg.com blob: data:; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.tailwindcss.com; "
             . "font-src 'self' https://fonts.gstatic.com https://fonts.googleapis.com data:; "
             . "img-src 'self' data: blob: https: http:; "
             . "connect-src 'self' https: http: ws: wss: data: blob:; "
             . "media-src 'self' blob: data: https: http:; "
             . "frame-src 'self' https: http:; "
             . "worker-src 'self' blob:;";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
