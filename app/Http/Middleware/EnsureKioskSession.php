<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows access to /kiosk/summary/{id}/... only when the server-side face match
 * (KioskController@identify) authenticated this exact patient within the last 10 minutes.
 */
class EnsureKioskSession
{
    public const TTL_SECONDS = 600;

    public function handle(Request $request, Closure $next): Response
    {
        $kioskId = $request->session()->get('kiosk_patient_id');
        $kioskAt = $request->session()->get('kiosk_auth_at');
        $expired = ! $kioskAt || (now()->timestamp - $kioskAt) > self::TTL_SECONDS;

        if ($expired || (string) $kioskId !== (string) $request->route('id')) {
            $request->session()->forget(['kiosk_patient_id', 'kiosk_auth_at']);

            return redirect()->route('welcome')->with('error', 'Session expired. Please scan your face again.');
        }

        return $next($request);
    }
}
