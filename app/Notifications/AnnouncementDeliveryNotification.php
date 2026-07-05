<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementDeliveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Announcement $announcement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = $this->announcement->isEventType() ? 'Event' : 'Announcement';

        return (new MailMessage)
            ->subject("{$typeLabel}: {$this->announcement->title}")
            ->greeting('Hello!')
            ->line("Marriott School has posted a new {$typeLabel}.")
            ->line("Title: {$this->announcement->title}")
            ->line($this->announcement->content)
            ->action("View {$typeLabel}", url("/notifications/announcements/{$this->announcement->id}"))
            ->line('Please open MarriottConnect for the complete details and any required response.')
            ->salutation('Marriott School');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => (int) $this->announcement->id,
            'title' => (string) $this->announcement->title,
        ];
    }
}
