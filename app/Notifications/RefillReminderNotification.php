<?php

namespace App\Notifications;

use App\Models\Medication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** One message per patient listing every medicine whose supply is about to run out. */
class RefillReminderNotification extends Notification
{
    use Queueable;

    /** @param  Collection<int, Medication>  $medications */
    public function __construct(protected Collection $medications)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Medication Refill Reminder - PharmaTrack')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your supply of the following ' . ($this->medications->count() === 1 ? 'medicine is' : 'medicines are') . ' running low:');

        foreach ($this->medications as $medication) {
            $mail->line('• ' . $medication->name . ' — ' . self::when($medication));
        }

        return $mail
            ->line('Please visit the pharmacy or request a refill so you do not miss any doses.')
            ->action('Request a Refill', url('/patient/medications'))
            ->line('If your doctor has stopped or changed this medicine, please let your pharmacist know.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Medication Refill Reminder',
            'message' => 'Running low: ' . $this->medications
                ->map(fn ($m) => $m->name . ' (' . self::when($m) . ')')
                ->implode(', ') . '. Visit the pharmacy or request a refill.',
            'medication_ids' => $this->medications->pluck('id')->all(),
            'action_url' => url('/patient/medications'),
            'type' => 'refill_due',
        ];
    }

    private static function when(Medication $medication): string
    {
        $days = $medication->supplyDaysLeft();

        return match (true) {
            $days < 0 => 'ran out on ' . $medication->supply_until->format('d M Y'),
            $days === 0 => 'runs out today',
            $days === 1 => 'runs out tomorrow',
            default => "runs out in {$days} days, on " . $medication->supply_until->format('d M Y'),
        };
    }
}
