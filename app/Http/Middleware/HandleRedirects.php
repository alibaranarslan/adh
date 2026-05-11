<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404) {
            $path = $request->path();
            $segments = explode('/', $path);
            $slug = end($segments);

            $redirect = Redirect::query()->where('old_slug', $slug)->first();

            if ($redirect) {
                $newPath = str_replace($slug, $redirect->new_slug, $path);

                return redirect($newPath, $redirect->status_code);
            }
        }

        return $response;
    }
}
