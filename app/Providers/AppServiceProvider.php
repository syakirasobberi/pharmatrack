<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        // Separate buckets so face-scan retries don't consume the budget for record updates.
        RateLimiter::for('kiosk-identify', fn (Request $request) => Limit::perMinute(30)->by('identify|' . $request->ip()));
        RateLimiter::for('kiosk-write', fn (Request $request) => Limit::perMinute(10)->by('write|' . $request->ip()));

        // Brevo sends over HTTPS (port 443). The live host blocks all outgoing SMTP ports, so
        // Gmail SMTP cannot work there. Enabled with MAIL_MAILER=brevo + BREVO_API_KEY in .env.
        Mail::extend('brevo', fn () => (new BrevoTransportFactory)->create(
            new Dsn('brevo+api', 'default', config('services.brevo.key'))
        ));
    }
}
