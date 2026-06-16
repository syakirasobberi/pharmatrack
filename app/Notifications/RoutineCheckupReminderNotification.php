<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoutineCheckupReminderNotification extends Notification
{
    use Queueable;

    protected $patient;

    /**
     * Create a new notification instance.
     */
    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $latestCheckup = $this->patient->healthCheckups()->latest('checkup_date')->first();
        $lastCheckupStr = $latestCheckup 
            ? 'Your last check-up was recorded on ' . $latestCheckup->checkup_date->format('d M Y') . '.'
            : 'You have no check-up on record yet.';

        return (new MailMessage)
            ->subject('Routine Health Check-up Reminder - PharmaTrack')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a friendly reminder that you are due for a routine health check-up.')
            ->line($lastCheckupStr)
            ->line('Regular clinical screenings of core vitals (like blood pressure, blood sugar, and cholesterol) are key to early detection of potential risks and managing your long-term health.')
            ->line('Please visit the pharmacy counter to complete your check-up at your earliest convenience.')
            ->action('View My Checkups', url('/patient/checkups'))
            ->line('Thank you for choosing PharmaTrack to support your health journey!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $latestCheckup = $this->patient->healthCheckups()->latest('checkup_date')->first();
        return [
            'title' => 'Routine Check-up Reminder',
            'message' => 'Your routine health check-up is due. Please visit the pharmacy counter.',
            'last_checkup_date' => $latestCheckup ? $latestCheckup->checkup_date->format('Y-m-d') : null,
            'action_url' => url('/patient/checkups'),
            'type' => 'checkup_due',
        ];
    }
}
