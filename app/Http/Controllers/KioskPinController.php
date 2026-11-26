<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KioskPinController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'pharmacist', 403);

        $validated = $request->validateWithBag('updateKioskPin', [
            'current_password' => ['required', 'current_password'],
            'kiosk_pin' => ['required', 'regex:/^\d{6,12}$/', 'confirmed'],
        ], [
            'kiosk_pin.regex' => __('The kiosk PIN must be 6 to 12 digits.'),
        ]);

        $user = $request->user();
        $user->kiosk_pin = $validated['kiosk_pin'];
        $user->save();

        return back()->with('status', 'kiosk-pin-updated');
    }
}
