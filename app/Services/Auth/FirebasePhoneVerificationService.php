<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebasePhoneVerificationService
{
    public function resolveVerifiedPhoneNumber(string $idToken): string
    {
        $firebaseApiKey = (string) config('services.firebase.api_key', '');

        if ($firebaseApiKey === '') {
            throw new RuntimeException('Firebase API key is not configured.');
        }

        $response = Http::asJson()->post(
            sprintf('https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=%s', $firebaseApiKey),
            [
                'idToken' => trim($idToken),
            ],
        );

        if (! $response->successful()) {
            throw new RuntimeException('Unable to verify phone OTP with Firebase.');
        }

        $phoneNumber = (string) data_get($response->json(), 'users.0.phoneNumber', '');

        if ($phoneNumber === '') {
            throw new RuntimeException('No verified phone number was returned by Firebase.');
        }

        return $this->normalizePhoneNumber($phoneNumber);
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
