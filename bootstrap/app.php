<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

if (PHP_SAPI === 'cli') {
    $artisanCommand = $_SERVER['argv'][1] ?? null;

    if (in_array($artisanCommand, ['route:cache', 'route:clear', 'optimize', 'optimize:clear'], true)) {
        $configuredRouteCachePath = $_ENV['APP_ROUTES_CACHE'] ?? $_SERVER['APP_ROUTES_CACHE'] ?? 'bootstrap/cache/routes-v7.php';
        $routeCachePath = str_starts_with($configuredRouteCachePath, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $configuredRouteCachePath) === 1
            ? $configuredRouteCachePath
            : dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($configuredRouteCachePath, '\\/');

        // Prevent stale Windows file locks from forcing Laravel to boot cached routes.
        if (is_file($routeCachePath)) {
            @unlink($routeCachePath);
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Defaulting to * on local lets any client spoof X-Forwarded-Proto (secure=true on plain HTTP),
        // which breaks session cookies when SESSION_SECURE_COOKIE=true → Filament 419.
        $trustedProxies = env('TRUSTED_PROXIES');
        $isLocalLike = in_array(env('APP_ENV', 'production'), ['local', 'development'], true);

        if ($trustedProxies === null || $trustedProxies === '') {
            $trustedProxies = $isLocalLike ? null : '*';
        } elseif ($isLocalLike && ($trustedProxies === '*' || $trustedProxies === '**')) {
            // Copied .env.example; trusting all proxies on plain HTTP allows forged X-Forwarded-Proto → 419.
            $trustedProxies = null;
        }

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->append(\App\Http\Middleware\ConfigureSessionCookieForRequest::class);
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

        $middleware->alias([
            'set.locale'       => \App\Http\Middleware\SetLocale::class,
            'seo.defaults'     => \App\Http\Middleware\SeoDefaults::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'handle.redirects' => \App\Http\Middleware\HandleRedirects::class,
            'cache.page'       => \App\Http\Middleware\CachePage::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SeoDefaults::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\HandleRedirects::class,
        ]);
        $middleware->append(\App\Http\Middleware\TrackPageView::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
