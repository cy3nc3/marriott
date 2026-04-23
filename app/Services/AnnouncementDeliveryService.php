<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementDeliveryNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class AnnouncementDeliveryService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $announcementAudienceResolver,
        private readonly AnnouncementSmsService $announcementSmsService,
    ) {}

    /**
     * @return array{email_sent: int, sms_sent: int, sms_skipped: int, warning: string|null}
     */
    public function deliverForAnnouncement(Announcement $announcement, User $organizer): array
    {
        if ($announcement->publish_at !== null && $announcement->publish_at->isFuture()) {
            return [
                'email_sent' => 0,
                'sms_sent' => 0,
                'sms_skipped' => 0,
                'warning' => 'Email/SMS was skipped because the announcement is scheduled for future publishing.',
            ];
        }

        $channels = $announcement->normalizedDeliveryChannels();
        $shouldSendEmail = in_array(Announcement::DELIVERY_CHANNEL_EMAIL, $channels, true);
        $shouldSendSms = in_array(Announcement::DELIVERY_CHANNEL_SMS, $channels, true);

        if (! $shouldSendEmail && ! $shouldSendSms) {
            return [
                'email_sent' => 0,
                'sms_sent' => 0,
                'sms_skipped' => 0,
                'warning' => null,
            ];
        }

        try {
            $recipients = $this->announcementAudienceResolver->resolveRecipients(
                $organizer,
                $this->normalizeStringArray($announcement->target_roles),
                $this->normalizeIntegerArray($announcement->target_user_ids),
            );
        } catch (ValidationException) {
            return [
                'email_sent' => 0,
                'sms_sent' => 0,
                'sms_skipped' => 0,
                'warning' => 'Email/SMS was skipped because no recipients matched the selected audience.',
            ];
        }

        $emailSent = 0;
        $smsSent = 0;
        $smsSkipped = 0;
        $warningReason = null;

        foreach ($recipients as $recipient) {
            if (! $recipient instanceof User) {
                continue;
            }

            if ($shouldSendEmail) {
                $recipientEmail = trim((string) $recipient->email);
                if ($recipientEmail !== '') {
                    Notification::route('mail', $recipientEmail)
                        ->notify(new AnnouncementDeliveryNotification($announcement));
                    $emailSent++;
                }
            }

            if ($shouldSendSms) {
                try {
                    $smsResult = $this->announcementSmsService->send($recipient, $announcement);
                } catch (Throwable) {
                    $smsResult = [
                        'sent' => false,
                        'reason' => 'SMS dispatch failed unexpectedly.',
                    ];
                }

                if ($smsResult['sent']) {
                    $smsSent++;
                } else {
                    $smsSkipped++;
                    if ($warningReason === null && is_string($smsResult['reason']) && $smsResult['reason'] !== '') {
                        $warningReason = $smsResult['reason'];
                    }
                }
            }
        }

        return [
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent,
            'sms_skipped' => $smsSkipped,
            'warning' => $warningReason,
        ];
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return array<int, string>
     */
    private function normalizeStringArray(?array $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>|null  $values
     * @return array<int, int>
     */
    private function normalizeIntegerArray(?array $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(function (mixed $value): ?int {
                if (is_int($value)) {
                    return $value;
                }

                if (is_string($value) && is_numeric($value)) {
                    return (int) $value;
                }

                return null;
            })
            ->filter(fn (?int $value): bool => $value !== null && $value > 0)
            ->unique()
            ->values()
            ->all();
    }
}
