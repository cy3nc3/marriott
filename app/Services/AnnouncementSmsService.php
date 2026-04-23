<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class AnnouncementSmsService
{
    /**
     * @return array{sent: bool, reason: string|null}
     */
    public function send(User $recipient, Announcement $announcement): array
    {
        if (! config('services.announcement_sms.enabled', false)) {
            return [
                'sent' => false,
                'reason' => 'SMS delivery is disabled.',
            ];
        }

        $provider = (string) config('services.announcement_sms.provider', 'firebase');

        if ($provider !== 'firebase') {
            return [
                'sent' => false,
                'reason' => 'Unsupported SMS provider configured.',
            ];
        }

        $firebaseMode = (string) config('services.announcement_sms.firebase_mode', 'auth_verification_only');
        if ($firebaseMode === 'auth_verification_only') {
            return [
                'sent' => false,
                'reason' => 'Firebase Auth only supports verification SMS, not custom announcement SMS.',
            ];
        }

        $endpoint = trim((string) config('services.announcement_sms.firebase_sms_endpoint', ''));
        if ($endpoint === '') {
            return [
                'sent' => false,
                'reason' => 'Firebase SMS endpoint is not configured.',
            ];
        }

        $phoneNumber = $this->resolvePhoneNumber($recipient);
        if ($phoneNumber === '') {
            return [
                'sent' => false,
                'reason' => 'Recipient has no mobile number.',
            ];
        }

        $response = Http::asJson()->post($endpoint, [
            'phone_number' => $phoneNumber,
            'title' => (string) $announcement->title,
            'content' => (string) $announcement->content,
            'announcement_id' => (int) $announcement->id,
        ]);

        if (! $response->successful()) {
            return [
                'sent' => false,
                'reason' => 'SMS provider request failed.',
            ];
        }

        return [
            'sent' => true,
            'reason' => null,
        ];
    }

    private function resolvePhoneNumber(User $recipient): string
    {
        $recipient->loadMissing(['studentProfile:id,user_id,contact_number', 'students:id,contact_number']);

        $studentPhone = (string) ($recipient->studentProfile?->contact_number ?? '');
        $parentPhone = (string) ($recipient->students->firstWhere('contact_number', '!=', null)?->contact_number ?? '');

        return $this->normalizePhoneNumber($studentPhone !== '' ? $studentPhone : $parentPhone);
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        return '';
    }
}
