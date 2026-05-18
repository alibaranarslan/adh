<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale')
            ?? $request->query('locale')
            ?? 'tr';

        if (in_array($locale, ['tr', 'en', 'ku'], true)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
            URL::defaults(['locale' => $locale]);
        }

        return $next($request);
    }
}
