<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffAccountClaimNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $accountName,
        private readonly string $accountEmail,
        private readonly string $claimUrl,
        private readonly string $expiresAtLabel,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Claim your MarriottConnect staff account')
            ->greeting("Hello {$this->accountName},")
            ->line('A MarriottConnect staff account has been created for you.')
            ->line("Your account email is {$this->accountEmail}.")
            ->line('Use the secure claim link below to set your password before signing in:')
            ->action('Claim Staff Account', $this->claimUrl)
            ->line("This claim link expires on {$this->expiresAtLabel}.")
            ->line('If you did not expect this message, please contact the school administrator.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'account_email' => $this->accountEmail,
            'claim_url' => $this->claimUrl,
            'expires_at_label' => $this->expiresAtLabel,
        ];
    }
}
