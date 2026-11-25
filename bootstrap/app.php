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
        $middleware->web(append: [\App\Http\Middleware\EnsureAccountActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
