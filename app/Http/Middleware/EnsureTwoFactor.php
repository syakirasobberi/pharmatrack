<?php

namespace App\Http\Middleware;

use App\Support\TwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff (admin, pharmacist) must finish two-step sign-in before any other page.
 * Runs on every web request (not per route), so no staff page can be missed.
 */
class EnsureTwoFactor
{
    /** Reachable while the code is still pending. */
    private const ALLOWED_ROUTES = ['two-factor.show', 'two-factor.verify', 'two-factor.resend', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! TwoFactor::appliesTo($user) || TwoFactor::passed($request, $user) || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Finish two-step sign-in first.'], 403);
        }

        return redirect()->route('two-factor.show');
    }
}
