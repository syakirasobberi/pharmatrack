<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChangeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->requires_password_change) {
            // Check if they are already on the password change routes or logging out
            if (! $request->routeIs('password.force-change', 'password.force-change.store', 'logout')) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
