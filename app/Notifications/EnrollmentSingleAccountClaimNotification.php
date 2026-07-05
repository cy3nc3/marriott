<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentSingleAccountClaimNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $accountLabel,
        private readonly string $claimUrl,
        private readonly string $expiresAtLabel,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Claim your MarriottConnect {$this->accountLabel} account")
            ->greeting('Hello!')
            ->line("Your MarriottConnect {$this->accountLabel} account is ready.")
            ->line('Use the secure claim link below to set the account password:')
            ->action("Claim {$this->accountLabel} Account", $this->claimUrl)
            ->line("This claim link expires on {$this->expiresAtLabel}.")
            ->line('If you did not expect this message, please contact school support.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'account_label' => $this->accountLabel,
            'claim_url' => $this->claimUrl,
            'expires_at_label' => $this->expiresAtLabel,
        ];
    }
}
