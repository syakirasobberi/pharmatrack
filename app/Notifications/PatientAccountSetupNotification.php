<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a pharmacist registers a patient: a one-time link to choose their own password.
 */
class PatientAccountSetupNotification extends Notification
{
    public function __construct(protected string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Set up your ' . config('app.name') . ' account')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your pharmacist has created a patient account for you. Please choose your own password to start using the patient portal.')
            ->action('Set My Password', $url)
            ->line("This link expires in {$minutes} minutes. If it expires, use \"Forgot your password?\" on the login page to get a new one.");
    }
}
