<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SeoDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        $noindex = $request->filled('page') || $request->filled('q');

        $path = $request->path();
        $defaultLocale = config('app.locale', 'tr');

        if (preg_match('#^' . preg_quote($defaultLocale, '#') . '(/|$)#', $path)) {
            $stripped = preg_replace('#^' . preg_quote($defaultLocale, '#') . '(/|$)#', '', $path, 1);
            $canonical = url('/' . ltrim($stripped, '/'));
        } else {
            $canonical = $request->url();
        }

        View::share('canonical', $canonical);
        View::share('noindex', $noindex);

        return $next($request);
    }
}
