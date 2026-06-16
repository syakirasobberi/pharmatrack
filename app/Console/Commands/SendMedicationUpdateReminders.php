<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Notifications\MedicationUpdateReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMedicationUpdateReminders extends Command
{
    protected $signature = 'medications:send-update-reminders';

    protected $description = 'Send email and system reminders when patient medication records have not been updated in the last 6 months';

    public function handle()
    {
        $this->info('Starting medication update reminder scan...');

        $patients = Patient::with(['user', 'medications'])->get();
        $sentCount = 0;
        $skippedCount = 0;

        foreach ($patients as $patient) {
            $user = $patient->user;

            if (! $user) {
                $this->warn("Skipping patient ID {$patient->id}: associated User account not found.");
                continue;
            }

            if ($patient->medications->isEmpty()) {
                continue;
            }

            $latestMedicationUpdate = $patient->medications->max('updated_at');
            $isDue = ! $latestMedicationUpdate
                || Carbon::parse($latestMedicationUpdate)->lt(Carbon::today()->subMonths(6));

            if (! $isDue) {
                continue;
            }

            $recentNotificationExists = $user->notifications()
                ->where('type', MedicationUpdateReminderNotification::class)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->exists();

            if ($recentNotificationExists) {
                $this->line("Skipped: {$user->name} - already reminded in the last 30 days.");
                $skippedCount++;
                continue;
            }

            $user->notify(new MedicationUpdateReminderNotification($patient));
            $this->info("Reminder sent to: {$user->name} (Email: {$user->email})");
            $sentCount++;
        }

        $this->info("Scan completed. Sent: {$sentCount}, Skipped: {$skippedCount}.");

        return self::SUCCESS;
    }
}
