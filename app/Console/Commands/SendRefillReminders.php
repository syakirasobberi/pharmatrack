<?php

namespace App\Console\Commands;

use App\Models\Medication;
use App\Notifications\RefillReminderNotification;
use Illuminate\Console\Command;
use Throwable;

class SendRefillReminders extends Command
{
    protected $signature = 'medications:send-refill-reminders';

    protected $description = 'Remind patients (email + portal) when a medicine supply runs out within ' . Medication::REFILL_NOTICE_DAYS . ' days';

    public function handle(): int
    {
        // Supply ends within the notice window, or ran out in the last 14 days
        // (older than that, the pharmacist follows up instead of more emails).
        $candidates = Medication::current()
            ->whereNotNull('supply_until')
            ->whereBetween('supply_until', [today()->subDays(14), today()->addDays(Medication::REFILL_NOTICE_DAYS)])
            ->whereHas('patient', fn ($q) => $q->whereNull('archived_at'))
            ->with('patient.user')
            ->get()
            // One reminder per supply period: skip if already sent for this supply_until date.
            ->filter(fn (Medication $m) => in_array($m->refillStatus(), ['due', 'overdue'], true)
                && ! $m->refill_reminder_sent_for?->equalTo($m->supply_until)
                && $m->refill_requested_at === null);

        $sent = 0;
        foreach ($candidates->groupBy('patient_id') as $medications) {
            $user = $medications->first()->patient?->user;
            if (! $user) {
                continue;
            }

            try {
                $user->notify(new RefillReminderNotification($medications->values()));
            } catch (Throwable $e) {
                // Mail failure: keep the reminder pending so it is retried tomorrow.
                $this->error("Could not notify {$user->name}: {$e->getMessage()}");
                report($e);
                continue;
            }

            foreach ($medications as $medication) {
                $medication->forceFill(['refill_reminder_sent_for' => $medication->supply_until])->save();
            }
            $this->info("Refill reminder sent to {$user->name}: " . $medications->pluck('name')->implode(', '));
            $sent++;
        }

        $this->info("Refill reminders sent: {$sent}.");

        return self::SUCCESS;
    }
}
