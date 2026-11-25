<?php

namespace App\Providers;

use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerAuditTrail();

        // Separate buckets so face-scan retries don't consume the budget for record updates.
        RateLimiter::for('kiosk-identify', fn (Request $request) => Limit::perMinute(30)->by('identify|' . $request->ip()));
        RateLimiter::for('kiosk-write', fn (Request $request) => Limit::perMinute(10)->by('write|' . $request->ip()));

        // Brevo sends over HTTPS (port 443). The live host blocks all outgoing SMTP ports, so
        // Gmail SMTP cannot work there. Enabled with MAIL_MAILER=brevo + BREVO_API_KEY in .env.
        Mail::extend('brevo', fn () => (new BrevoTransportFactory)->create(
            new Dsn('brevo+api', 'default', config('services.brevo.key'))
        ));
    }

    /** Audit trail: record changes to clinical records/accounts and every sign-in attempt. */
    private function registerAuditTrail(): void
    {
        foreach ([\App\Models\Patient::class, \App\Models\Medication::class, \App\Models\HealthCheckup::class,
            \App\Models\MedicalHistory::class, \App\Models\User::class] as $model) {
            $model::observe(\App\Observers\AuditObserver::class);
        }

        Event::listen(Login::class, fn (Login $e) => Audit::record('auth.login', 'Signed in', actor: $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user && Audit::record('auth.logout', 'Signed out', actor: $e->user));
        Event::listen(Failed::class, function (Failed $e) {
            // The typed email is kept (to spot attacks on an account); the password never is.
            $email = Str::limit((string) ($e->credentials['email'] ?? ''), 100);
            Audit::record('auth.login_failed', "Failed sign-in attempt for \"{$email}\"", actor: $e->user);
        });
        Event::listen(Lockout::class, fn (Lockout $e) => Audit::record(
            'auth.locked_out',
            'Too many failed sign-in attempts for "' . Str::limit((string) $e->request->input('email'), 100) . '" — temporarily blocked',
        ));
    }
}
