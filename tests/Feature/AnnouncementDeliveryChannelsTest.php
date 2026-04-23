<?php

use App\Models\Announcement;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AnnouncementDeliveryNotification;
use Illuminate\Support\Facades\Notification;

it('stores selected delivery channels and sends announcement email via resend channel routing', function (): void {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create([
        'email' => 'teacher-recipient@example.test',
    ]);

    $this->actingAs($admin)
        ->post('/announcements', [
            'title' => 'Weather Advisory',
            'content' => 'Classes are suspended for tomorrow.',
            'target_roles' => ['teacher'],
            'target_user_ids' => [$teacher->id],
            'delivery_channels' => ['in_app', 'email'],
        ])
        ->assertRedirect();

    $announcement = Announcement::query()->latest('id')->firstOrFail();

    expect($announcement->normalizedDeliveryChannels())
        ->toContain('in_app')
        ->toContain('email');

    Notification::assertSentOnDemand(
        AnnouncementDeliveryNotification::class,
        function (AnnouncementDeliveryNotification $notification, array $channels, object $notifiable) use ($announcement, $teacher): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $teacher->email
                && $notification->toArray($notifiable)['announcement_id'] === $announcement->id;
        }
    );
});

it('shows warning when sms is selected but firebase backend is in auth verification mode', function (): void {
    config()->set('services.announcement_sms.enabled', true);
    config()->set('services.announcement_sms.provider', 'firebase');
    config()->set('services.announcement_sms.firebase_mode', 'auth_verification_only');

    $admin = User::factory()->admin()->create();
    $studentUser = User::factory()->student()->create();

    Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => '300000000001',
        'first_name' => 'Sms',
        'last_name' => 'Recipient',
        'contact_number' => '09171234567',
    ]);

    $this->actingAs($admin)
        ->from('/announcements')
        ->post('/announcements', [
            'title' => 'SMS-only Advisory',
            'content' => 'This should trigger SMS fallback warning.',
            'target_roles' => ['student'],
            'target_user_ids' => [$studentUser->id],
            'delivery_channels' => ['in_app', 'sms'],
        ])
        ->assertRedirect('/announcements')
        ->assertSessionHas('warning', 'Firebase Auth only supports verification SMS, not custom announcement SMS.');
});
