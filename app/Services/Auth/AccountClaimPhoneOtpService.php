<?php

namespace App\Services\Auth;

use App\Models\AccountClaimPhoneOtp;
use App\Models\AccountClaimToken;
use Illuminate\Support\Str;
use RuntimeException;

class AccountClaimPhoneOtpService
{
    public function assertPhoneMatches(AccountClaimToken $claimToken, string $phoneNumber): string
    {
        $expectedPhoneNumber = $this->resolveExpectedPhoneNumber($claimToken);
        $normalizedInputPhone = $this->normalizePhoneNumber($phoneNumber);

        if ($normalizedInputPhone !== $expectedPhoneNumber) {
            throw new RuntimeException('The phone number does not match the enrollment record.');
        }

        return $expectedPhoneNumber;
    }

    public function recordVerifiedPhone(AccountClaimToken $claimToken, string $phoneNumber): void
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $expectedPhoneNumber = $this->resolveExpectedPhoneNumber($claimToken);

        if ($normalizedPhone !== $expectedPhoneNumber) {
            throw new RuntimeException('The verified phone number does not match enrollment records.');
        }

        AccountClaimPhoneOtp::query()->create([
            'account_claim_token_id' => $claimToken->id,
            'user_id' => $claimToken->user_id,
            'enrollment_id' => $claimToken->enrollment_id,
            'phone_number' => $normalizedPhone,
            'code_hash' => hash('sha256', Str::random(40)),
            'expires_at' => now(),
            'verified_at' => now(),
            'attempts' => 1,
        ]);
    }

    public function resolveExpectedPhoneNumber(AccountClaimToken $claimToken): string
    {
        $claimToken->loadMissing('enrollment.student');

        $phoneNumber = (string) ($claimToken->enrollment?->student?->contact_number ?? '');
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        if ($normalizedPhone === '') {
            throw new RuntimeException('No phone number is available for this enrollment.');
        }

        return $normalizedPhone;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        return '';
    }
}
