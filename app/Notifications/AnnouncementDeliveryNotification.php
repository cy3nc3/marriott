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
            ->line($this->announcement->content)
            ->action('View Announcement', url("/notifications/announcements/{$this->announcement->id}"))
            ->line('You are receiving this because you are part of the target audience.');
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
