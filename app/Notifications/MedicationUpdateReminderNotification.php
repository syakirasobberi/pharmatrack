<?php

namespace App\Notifications;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicationUpdateReminderNotification extends Notification
{
    use Queueable;

    public function __construct(protected Patient $patient)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lastUpdate = $this->latestMedicationUpdate();
        $lastUpdateText = $lastUpdate
            ? 'Your medication list was last updated on ' . $lastUpdate->format('d M Y') . '.'
            : 'You do not have a medication update recorded yet.';

        return (new MailMessage)
            ->subject('Medication Update Reminder - PharmaTrack')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Please review, add, or update your medication list in PharmaTrack.')
            ->line($lastUpdateText)
            ->line('Keeping this information current helps your pharmacist monitor your treatment and follow up when needed.')
            ->action('Update My Medications', url('/patient/medications'))
            ->line('If your medication has changed or you are unsure what to update, please contact the pharmacy counter.');
    }

    public function toArray(object $notifiable): array
    {
        $lastUpdate = $this->latestMedicationUpdate();

        return [
            'title' => 'Medication Update Reminder',
            'message' => 'Please review, add, or update your medication list. It should be updated at least once every 6 months.',
            'last_medication_update' => $lastUpdate?->format('Y-m-d'),
            'action_url' => url('/patient/medications'),
            'type' => 'medication_update_due',
        ];
    }

    private function latestMedicationUpdate(): ?Carbon
    {
        $latest = $this->patient->medications()
            ->latest('updated_at')
            ->value('updated_at');

        return $latest ? Carbon::parse($latest) : null;
    }
}
