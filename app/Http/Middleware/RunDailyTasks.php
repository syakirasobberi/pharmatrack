<?php

namespace App\Http\Middleware;

use App\Support\DailyTasks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Terminable: runs the daily reminders after the response has been sent (see DailyTasks). */
class RunDailyTasks
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        DailyTasks::runIfDue();
    }
}
