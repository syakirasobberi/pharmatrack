<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Check if the user is logged in and has the correct role
        if ($request->user() && $request->user()->role !== $role) {
            // If they don't have the right role, show a 403 Forbidden error
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}