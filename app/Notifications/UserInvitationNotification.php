<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $plainToken) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $url = route('activation.show', ['token' => $this->plainToken]);

        return (new MailMessage)
            ->subject('Activate your '.config('app.name').' account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An administrator invited you to join '.config('app.name').'.')
            ->line('Use the button below to set your password and activate your account. This link expires in 7 days.')
            ->action('Activate account', $url)
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
