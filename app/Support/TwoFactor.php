<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

/**
 * Two-step sign-in for staff: after the password, a 6-digit code is emailed and must be entered.
 *
 * Codes live in the cache (hashed, 10 minutes, 5 tries). "Remember this device" sets an
 * encrypted cookie for 30 days, signed with the password hash, so changing the password
 * cancels every remembered device. Emergency switch: TWO_FACTOR_ENABLED=false in .env.
 */
class TwoFactor
{
    public const ROLES = ['admin', 'pharmacist'];

    public const CODE_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_SECONDS = 60;

    public const REMEMBER_DAYS = 30;

    public const COOKIE = 'pt_trusted_device';

    private const SESSION_KEY = 'two_factor_passed';

    public static function enabled(): bool
    {
        return (bool) config('app.two_factor');
    }

    public static function appliesTo(?User $user): bool
    {
        return $user !== null && self::enabled() && in_array($user->role, self::ROLES, true);
    }

    /** Passed in this session, or on a remembered device (which then marks the session). */
    public static function passed(Request $request, User $user): bool
    {
        if ($request->session()->get(self::SESSION_KEY) === $user->id) {
            return true;
        }

        if (self::deviceRemembered($request, $user)) {
            self::markPassed($request, $user);

            return true;
        }

        return false;
    }

    public static function markPassed(Request $request, User $user): void
    {
        $request->session()->put(self::SESSION_KEY, $user->id);
    }

    /** True if a still-valid code was already sent (so opening the page again does not spam email). */
    public static function hasActiveCode(User $user): bool
    {
        $entry = Cache::get(self::cacheKey($user));

        return $entry !== null && $entry['expires_at'] > now()->timestamp && $entry['attempts'] < self::MAX_ATTEMPTS;
    }

    public static function secondsUntilResend(User $user): int
    {
        $entry = Cache::get(self::cacheKey($user));

        return $entry ? max(0, $entry['sent_at'] + self::RESEND_SECONDS - now()->timestamp) : 0;
    }

    /** Creates a new code, emails it, and replaces any earlier code. */
    public static function send(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(self::cacheKey($user), [
            'hash' => self::hashCode($user, $code),
            'expires_at' => now()->addMinutes(self::CODE_MINUTES)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_MINUTES));

        $user->notify(new TwoFactorCodeNotification($code, self::CODE_MINUTES));
    }

    /** 'ok', 'wrong', 'expired' (none, timed out, or too many tries). A used code cannot be used again. */
    public static function verify(User $user, string $code): string
    {
        $key = self::cacheKey($user);
        $entry = Cache::get($key);

        if ($entry === null || $entry['expires_at'] <= now()->timestamp || $entry['attempts'] >= self::MAX_ATTEMPTS) {
            return 'expired';
        }

        if (! hash_equals($entry['hash'], self::hashCode($user, preg_replace('/\D/', '', $code)))) {
            $entry['attempts']++;
            Cache::put($key, $entry, now()->setTimestamp($entry['expires_at']));

            return $entry['attempts'] >= self::MAX_ATTEMPTS ? 'expired' : 'wrong';
        }

        Cache::forget($key);

        return 'ok';
    }

    public static function rememberDevice(User $user): void
    {
        $expires = now()->addDays(self::REMEMBER_DAYS)->timestamp;

        Cookie::queue(Cookie::make(
            self::COOKIE,
            json_encode(['uid' => $user->id, 'exp' => $expires, 'sig' => self::deviceSignature($user, $expires)]),
            self::REMEMBER_DAYS * 24 * 60,
            null, null, null, true, false, 'lax',   // HttpOnly; Laravel also encrypts it
        ));
    }

    private static function deviceRemembered(Request $request, User $user): bool
    {
        $data = json_decode((string) $request->cookie(self::COOKIE), true);

        return is_array($data)
            && ($data['uid'] ?? null) === $user->id
            && ($data['exp'] ?? 0) > now()->timestamp
            && hash_equals(self::deviceSignature($user, (int) $data['exp']), (string) ($data['sig'] ?? ''));
    }

    /** Tied to the password hash: a password change invalidates every remembered device. */
    private static function deviceSignature(User $user, int $expires): string
    {
        return hash_hmac('sha256', $user->id . '|' . $expires . '|' . $user->password, (string) config('app.key'));
    }

    private static function hashCode(User $user, string $code): string
    {
        return hash_hmac('sha256', $user->id . '|' . $code, (string) config('app.key'));
    }

    private static function cacheKey(User $user): string
    {
        return 'two-factor:' . $user->id;
    }
}
