<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentAccountClaimNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $claimUrl,
        private readonly string $expiresAtLabel,
        private readonly string $accountEmail,
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
            ->subject('Your MarriottConnect account is ready')
            ->greeting('Hello!')
            ->line('Your enrollment has been approved, and your MarriottConnect account is ready.')
            ->line("Account email: {$this->accountEmail}")
            ->action('Set your password', $this->claimUrl)
            ->line("This claim link expires on {$this->expiresAtLabel}.")
            ->line('If you did not expect this message, please contact school support.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'claim_url' => $this->claimUrl,
            'expires_at_label' => $this->expiresAtLabel,
            'account_email' => $this->accountEmail,
        ];
    }
}
