<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The 6-digit sign-in code for staff two-step sign-in. */
class TwoFactorCodeNotification extends Notification
{
    public function __construct(public readonly string $code, protected int $minutes)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->code . ' is your ' . config('app.name') . ' sign-in code')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your sign-in code is:')
            ->line('**' . $this->code . '**')
            ->line("It expires in {$this->minutes} minutes and can be used once.")
            ->line('If you did not just sign in, someone may know your password. Change it now and tell the administrator.');
    }
}
