<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
    }
}
