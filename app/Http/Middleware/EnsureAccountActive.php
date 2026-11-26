<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user whose account was deactivated while they were logged in.
 * Runs on every web request, so deactivation takes effect on their next click.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDeactivated()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'This account has been deactivated.'], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => __('This account has been deactivated. Please contact the administrator.')]);
        }

        return $next($request);
    }
}
