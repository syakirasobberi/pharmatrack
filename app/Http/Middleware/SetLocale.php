<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language the visitor picked with the EN / BM toggle (stored in the session).
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'ms'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        // No choice (or cleared after a kiosk check-in): English. Not config('app.locale'),
        // because App::setLocale() overwrites that value.
        App::setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : config('app.fallback_locale', 'en'));

        return $next($request);
    }
}
