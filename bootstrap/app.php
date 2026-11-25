<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware; 
use App\Http\Middleware\ForcePasswordChangeMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add your alias here
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'force_password_change' => ForcePasswordChangeMiddleware::class,
            'kiosk.session' => \App\Http\Middleware\EnsureKioskSession::class,
        ]);

        // Every web request: sign out accounts an admin has deactivated.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAccountActive::class,
            // Back button after logout must not show cached patient pages.
            \App\Http\Middleware\PreventCachingWhenLoggedIn::class,
            // Daily reminders (no cron on the live host); runs after the response is sent.
            \App\Http\Middleware\RunDailyTasks::class,
            // Audit trail of who viewed or sent out patient data.
            \App\Http\Middleware\AuditAccess::class,
        ]);

        // Logout works even from a page left open past the session lifetime (120 min):
        // otherwise its expired CSRF token gives "419 Page Expired" and the user stays
        // stuck. Logging someone out is harmless, so it needs no CSRF protection.
        $middleware->validateCsrfTokens(except: ['logout']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
