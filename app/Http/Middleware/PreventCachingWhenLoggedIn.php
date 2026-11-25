<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages shown to a logged-in user must not be stored by the browser. Otherwise pressing
 * Back after logging out shows the dashboard (and patient data) from the cache, which
 * looks as if the logout did not work.
 */
class PreventCachingWhenLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() || $request->routeIs('logout')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
