<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        $isAdminPanel = str_starts_with($request->path(), 'admin');

        if ($isAdminPanel) {
            // Filament/Livewire needs unsafe-inline and unsafe-eval
            $csp = "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data: blob:; frame-ancestors 'self';";
        } else {
            // Public frontend — stricter policy
            $csp = "default-src 'self' https:; script-src 'self' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com https://pagead2.googlesyndication.com 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' https: data: blob:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https:; frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com; frame-ancestors 'self';";
        }

        $reportUri = env('CSP_REPORT_URI');
        if (filled($reportUri)) {
            $csp .= " report-uri {$reportUri};";
        }

        // Production CSP blocks Vite dev assets (different host/port than the app). Skip only when Vite is hot.
        $viteHot = public_path('hot');
        if (! (app()->environment('local') && is_file($viteHot))) {
            $headerName = filter_var(env('CSP_REPORT_ONLY', false), FILTER_VALIDATE_BOOL)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($headerName, $csp);
        }

        if ($request->secure() || str_starts_with((string) config('app.url', ''), 'https://')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
