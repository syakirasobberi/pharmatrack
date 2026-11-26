<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/** The "enter the code we emailed you" step for staff. */
class TwoFactorController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! TwoFactor::appliesTo($user) || TwoFactor::passed($request, $user)) {
            return redirect()->route('dashboard');
        }

        // Send a code on arrival, but not again on every reload while one is still valid.
        if (! TwoFactor::hasActiveCode($user)) {
            if ($error = $this->sendCode($user)) {
                return view('auth.two-factor', ['email' => $user->email])->withErrors(['code' => $error]);
            }
        }

        return view('auth.two-factor', ['email' => $user->email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'max:12']]);

        $result = TwoFactor::verify($user, $request->input('code'));

        if ($result !== 'ok') {
            Audit::record('auth.two_factor_failed', 'Wrong or expired sign-in code');

            return back()->withErrors(['code' => $result === 'expired'
                ? 'This code has expired or was tried too many times. Click "Send a new code".'
                : 'That code is not correct. Check the latest email and try again.']);
        }

        TwoFactor::markPassed($request, $user);
        $request->session()->regenerate();   // new session id once fully signed in
        if ($request->boolean('remember_device')) {
            TwoFactor::rememberDevice($user);
        }
        Audit::record('auth.two_factor_passed', 'Completed two-step sign-in' . ($request->boolean('remember_device') ? ' (device remembered for ' . TwoFactor::REMEMBER_DAYS . ' days)' : ''));

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (($wait = TwoFactor::secondsUntilResend($user)) > 0) {
            return back()->withErrors(['code' => "Please wait {$wait} seconds before asking for another code."]);
        }

        if ($error = $this->sendCode($user)) {
            return back()->withErrors(['code' => $error]);
        }

        return back()->with('status', 'A new code has been sent to your email.');
    }

    /** Returns an error message if the email could not be sent. */
    private function sendCode($user): ?string
    {
        try {
            TwoFactor::send($user);
            Audit::record('auth.two_factor_code_sent', 'Sign-in code emailed');

            return null;
        } catch (Throwable $e) {
            report($e);

            return 'The sign-in code could not be emailed. Please try again in a few minutes, or contact the administrator.';
        }
    }
}
