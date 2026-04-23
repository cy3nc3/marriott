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
        private readonly ?string $studentClaimUrl,
        private readonly ?string $parentClaimUrl,
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
        $mailMessage = (new MailMessage)
            ->subject('Your MarriottConnect account is ready')
            ->greeting('Hello!')
            ->line('Your enrollment has been approved, and your MarriottConnect accounts are ready.')
            ->line('Use the links below to set each account password:')
            ->line($this->studentClaimUrl
                ? "Claim Student Account: {$this->studentClaimUrl}"
                : 'Claim Student Account: Not available')
            ->line($this->parentClaimUrl
                ? "Claim Parent Account: {$this->parentClaimUrl}"
                : 'Claim Parent Account: Not available')
            ->line("These claim links expire on {$this->expiresAtLabel}.")
            ->line('If you did not expect this message, please contact school support.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_claim_url' => $this->studentClaimUrl,
            'parent_claim_url' => $this->parentClaimUrl,
            'expires_at_label' => $this->expiresAtLabel,
        ];
    }
}
