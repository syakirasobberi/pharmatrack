<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Support\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Staff two-step sign-in with an emailed code. */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.two_factor' => true]);
        Notification::fake();
    }

    private function login(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    /** The code from the most recent email to $user. */
    private function lastCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_staff_must_enter_the_emailed_code_before_any_page(): void
    {
        $pharmacist = $this->pharmacist();
        $this->login($pharmacist);

        foreach (['/pharmacist/dashboard', '/pharmacist/patients', '/profile', '/dashboard', '/force-password-change'] as $page) {
            $this->get($page)->assertRedirect(route('two-factor.show'));
        }
        $this->postJson('/api/generate-ai-suggestion', [])->assertForbidden();

        $this->get('/two-factor')->assertOk()->assertSee($pharmacist->email);
        $code = $this->lastCode($pharmacist);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->post('/two-factor', ['code' => $code])->assertRedirect();
        $this->get('/pharmacist/dashboard')->assertOk();
        $this->assertTrue(AuditLog::where('action', 'auth.two_factor_passed')->exists());
    }

    public function test_admin_is_also_protected(): void
    {
        $admin = $this->admin();
        $this->login($admin);

        $this->get('/admin/settings')->assertRedirect(route('two-factor.show'));
        $this->get('/two-factor');
        $this->post('/two-factor', ['code' => $this->lastCode($admin)]);
        $this->get('/admin/settings')->assertOk();
    }

    public function test_patients_are_not_asked_for_a_code(): void
    {
        $patient = $this->patientOf($this->pharmacist())->user;
        $this->login($patient);

        $this->get('/patient/dashboard')->assertOk();
        Notification::assertNothingSent();
    }

    public function test_wrong_code_is_refused_and_five_wrong_tries_kill_the_code(): void
    {
        $pharmacist = $this->pharmacist();
        $this->login($pharmacist);
        $this->get('/two-factor');
        $code = $this->lastCode($pharmacist);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->post('/two-factor', ['code' => $wrong])->assertSessionHasErrors('code');
        $this->get('/pharmacist/dashboard')->assertRedirect(route('two-factor.show'));

        for ($i = 2; $i <= TwoFactor::MAX_ATTEMPTS; $i++) {
            $this->post('/two-factor', ['code' => $wrong]);
        }
        // Even the right code no longer works after too many wrong tries.
        $this->post('/two-factor', ['code' => $code])->assertSessionHasErrors('code');
        $this->get('/pharmacist/dashboard')->assertRedirect(route('two-factor.show'));
        $this->assertTrue(AuditLog::where('action', 'auth.two_factor_failed')->exists());
    }

    public function test_code_expires_after_ten_minutes_and_works_only_once(): void
    {
        $pharmacist = $this->pharmacist();
        $this->login($pharmacist);
        $this->get('/two-factor');
        $code = $this->lastCode($pharmacist);

        $this->travel(TwoFactor::CODE_MINUTES + 1)->minutes();
        $this->post('/two-factor', ['code' => $code])->assertSessionHasErrors('code');
        $this->travelBack();

        TwoFactor::send($pharmacist);
        $fresh = $this->lastCode($pharmacist);
        $this->assertSame('ok', TwoFactor::verify($pharmacist, $fresh));
        $this->assertSame('expired', TwoFactor::verify($pharmacist, $fresh), 'a used code must not work again');
    }

    public function test_reloading_the_page_does_not_send_another_email_and_resend_is_rate_limited(): void
    {
        $pharmacist = $this->pharmacist();
        $this->login($pharmacist);

        $this->get('/two-factor');
        $this->get('/two-factor');
        Notification::assertSentToTimes($pharmacist, TwoFactorCodeNotification::class, 1);

        $this->post('/two-factor/resend')->assertSessionHasErrors('code');   // within 60 seconds
        $this->travel(TwoFactor::RESEND_SECONDS + 1)->seconds();
        $this->post('/two-factor/resend')->assertSessionHas('status');
        Notification::assertSentToTimes($pharmacist, TwoFactorCodeNotification::class, 2);
    }

    public function test_remembered_device_skips_the_code_until_the_password_changes(): void
    {
        $pharmacist = $this->pharmacist();
        $this->login($pharmacist);
        $this->get('/two-factor');
        $response = $this->post('/two-factor', ['code' => $this->lastCode($pharmacist), 'remember_device' => '1']);
        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === TwoFactor::COOKIE);
        $this->assertNotNull($cookie, 'remember-device cookie was not set');

        // New session (log out and back in) on the same device: no code needed.
        $this->post('/logout');
        // The response value is already encrypted: send it back as-is, like a browser does.
        $this->withUnencryptedCookie(TwoFactor::COOKIE, $cookie->getValue());
        $this->login($pharmacist);
        $this->get('/pharmacist/dashboard')->assertOk();

        // Password changed: the remembered device no longer counts.
        $this->post('/logout');
        $pharmacist->forceFill(['password' => Hash::make('a-new-password-123')])->save();
        $this->post('/login', ['email' => $pharmacist->email, 'password' => 'a-new-password-123']);
        $this->get('/pharmacist/dashboard')->assertRedirect(route('two-factor.show'));
    }

    public function test_forged_or_other_users_device_cookie_is_ignored(): void
    {
        $pharmacist = $this->pharmacist();
        $this->withCookie(TwoFactor::COOKIE, json_encode(['uid' => $pharmacist->id, 'exp' => now()->addDay()->timestamp, 'sig' => 'forged']));
        $this->login($pharmacist);

        $this->get('/pharmacist/dashboard')->assertRedirect(route('two-factor.show'));
    }

    public function test_switch_off_skips_two_step_sign_in(): void
    {
        config(['app.two_factor' => false]);
        $pharmacist = $this->pharmacist();
        $this->login($pharmacist);

        $this->get('/pharmacist/dashboard')->assertOk();
        Notification::assertNothingSent();
    }

    public function test_two_factor_comes_before_the_forced_password_change(): void
    {
        $pharmacist = $this->pharmacist(['requires_password_change' => true]);
        $this->login($pharmacist);

        $this->get('/pharmacist/dashboard')->assertRedirect(route('two-factor.show'));
        $this->get('/two-factor');
        $this->post('/two-factor', ['code' => $this->lastCode($pharmacist)]);
        $this->get('/pharmacist/dashboard')->assertRedirect(route('password.force-change'));
    }
}
