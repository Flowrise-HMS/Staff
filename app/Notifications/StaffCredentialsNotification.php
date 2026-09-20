<?php

namespace Modules\Staff\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Core\Notifications\Concerns\RespectsNotificationSettings;
use Modules\Core\Support\AppSettings;

class StaffCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationSettings;

    public function __construct(
        protected string $password
    ) {}

    /**
     * The mail channel honours the "Send credentials email on account create"
     * notification setting; the database entry is always written so the user
     * sees that an account was provisioned (without the password).
     */
    public function via(object $notifiable): array
    {
        try {
            $mailEnabled = app(AppSettings::class)->notifications()->staff_credentials_mail;
        } catch (\Throwable) {
            $mailEnabled = true;
        }

        return $this->applyNotificationSettings(['mail', 'database'], $mailEnabled, false);
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

    /**
     * Never persist the plaintext password in the notifications table.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'staff_credentials',
            'message' => 'Your account credentials have been sent to your email.',
        ];
    }
}
