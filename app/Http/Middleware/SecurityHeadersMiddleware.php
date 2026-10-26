<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach defensive HTTP security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Defensive headers for W3HealthChecker
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content-Security-Policy compatible with Vite, React, Google Fonts, and local dev
        // Content-Security-Policy compatible with Vite, React, Google Fonts, Adsterra, Monetag, and analytics
        $csp = "default-src 'self' https: data: blob:; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:; " .
               "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https:; " .
               "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data: https:; " .
               "img-src 'self' data: https: blob:; " .
               "connect-src 'self' http: https: ws: wss:; " .
               "frame-src 'self' https:; " .
               "worker-src 'self' blob: https:; " .
               "frame-ancestors 'self';";
        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS only if request is over HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
