<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Avoids missing session cookies that show up as 419 "Page expired" on Filament / Livewire.
 *
 * Typical causes: Secure flag on plain HTTP, empty SESSION_DOMAIN in .env, or
 * trusting all proxies while APP_URL is still http:// so forwarded proto disagrees with the browser connection.
 */
class ConfigureSessionCookieForRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = config('session.domain');
        if ($domain === '' || $domain === 'null') {
            config(['session.domain' => null]);
        }

        $appUrl = (string) config('app.url', '');

        if (str_starts_with($appUrl, 'http://')) {
            config([
                'session.secure' => false,
                'session.same_site' => 'lax',
            ]);

            return $next($request);
        }

        if (! $request->secure()) {
            config([
                'session.secure' => false,
                'session.same_site' => 'lax',
            ]);
        }

        return $next($request);
    }
}
