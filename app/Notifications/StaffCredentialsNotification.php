<?php

namespace Modules\Staff\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $password
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.config('app.name').' Account Credentials')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your account has been created in the '.config('app.name').' Hospital Management System.')
            ->line('**Your login credentials:**')
            ->line('**Email:** '.$notifiable->email)
            ->line('**Username:** '.$notifiable->username)
            ->line('**Password:** '.$this->password)
            ->line('')
            ->line('Please change your password after your first login.')
            ->action('Login Now', url('/login'))
            ->line('If you have any issues, please contact the system administrator.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'staff_credentials',
            'message' => 'Your account credentials have been sent to your email.',
            'password' => $this->password,
        ];
    }
}
