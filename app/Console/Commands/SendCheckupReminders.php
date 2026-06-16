<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Notifications\RoutineCheckupReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCheckupReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkups:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated system and email check-up reminders to patients whose last checkup was > 90 days ago or never';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automated check-up reminders scan...');

        $patients = Patient::with(['user', 'healthCheckups' => function ($q) {
            $q->latest('checkup_date');
        }])->get();

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($patients as $patient) {
            $user = $patient->user;

            if (!$user) {
                $this->warn("Skipping patient ID {$patient->id}: associated User account not found.");
                continue;
            }

            $latestCheckup = $patient->healthCheckups->first();
            $isOverdue = !$latestCheckup || Carbon::parse($latestCheckup->checkup_date)->lt(Carbon::today()->subDays(90));

            if ($isOverdue) {
                // Anti-spam check: check if the notification was sent in the last 7 days
                $recentNotificationExists = $user->notifications()
                    ->where('type', RoutineCheckupReminderNotification::class)
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->exists();

                if (!$recentNotificationExists) {
                    $user->notify(new RoutineCheckupReminderNotification($patient));
                    $this->info("✓ Reminder sent to: {$user->name} (Email: {$user->email})");
                    $sentCount++;
                } else {
                    $this->line("• Skipped: {$user->name} - already reminded in the last 7 days.");
                    $skippedCount++;
                }
            }
        }

        $this->info("Scan completed. Sent: {$sentCount}, Skipped: {$skippedCount}.");

        return self::SUCCESS;
    }
}
