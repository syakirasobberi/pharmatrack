<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the daily reminder commands once a day, triggered by ordinary web traffic.
 *
 * The live host has no SSH/cron access for `php artisan schedule:run`, so the scheduled
 * reminders in routes/console.php never ran there. RunDailyTasks middleware calls this after
 * the response has been sent to the visitor, so nobody waits for it. A cache lock makes it run
 * at most once per day; the commands' own "already reminded" checks make a second run (e.g. if
 * a real cron is added later) harmless.
 */
class DailyTasks
{
    /** Commands to run once a day (also scheduled in routes/console.php for servers with cron). */
    public const COMMANDS = [
        'checkups:send-reminders',
        'medications:send-update-reminders',
        'medications:send-refill-reminders',
    ];

    /** Not before this hour (Malaysia time), so reminder emails arrive in the morning, not at midnight. */
    public const START_HOUR = 8;

    public static function runIfDue(): void
    {
        if (! config('app.daily_tasks_on_web') || now()->hour < self::START_HOUR) {
            return;
        }

        try {
            // Atomic: only the first request of the day gets the lock.
            if (! Cache::add('daily-tasks:' . today()->toDateString(), now()->toDateTimeString(), now()->addDays(2))) {
                return;
            }
        } catch (Throwable $e) {
            report($e);

            return;
        }

        ignore_user_abort(true);
        @set_time_limit(300);

        foreach (self::COMMANDS as $command) {
            try {
                Artisan::call($command);
                Log::info("Daily task {$command} finished.", ['output' => trim(Artisan::output())]);
            } catch (Throwable $e) {
                // One failing command (e.g. email down) must not stop the others.
                Log::error("Daily task {$command} failed.", ['error' => $e->getMessage()]);
            }
        }
    }
}
