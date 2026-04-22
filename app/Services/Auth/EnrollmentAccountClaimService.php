<?php

namespace App\Services\Auth;

use App\Models\AccountClaimToken;
use App\Models\Enrollment;
use App\Notifications\EnrollmentAccountClaimNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

class EnrollmentAccountClaimService
{
    private const CLAIM_LINK_EXPIRATION_HOURS = 24 * 30;

    public function issueForEnrollment(Enrollment $enrollment): ?AccountClaimToken
    {
        $tokenPayload = $this->issueTokenPayloadForEnrollment($enrollment, sendNotification: true);

        return $tokenPayload['token'] ?? null;
    }

    public function issuePlainTokenForEnrollment(Enrollment $enrollment): ?string
    {
        $tokenPayload = $this->issueTokenPayloadForEnrollment($enrollment, sendNotification: false);

        return $tokenPayload['plain_token'] ?? null;
    }

    /**
     * @return array{token: AccountClaimToken, plain_token: string}|null
     */
    private function issueTokenPayloadForEnrollment(Enrollment $enrollment, bool $sendNotification): ?array
    {
        if ((string) $enrollment->status !== 'enrolled') {
            return null;
        }

        $recipientEmail = trim((string) $enrollment->email);
        if ($recipientEmail === '') {
            return null;
        }

        $enrollment->loadMissing('student.user');
        $user = $enrollment->student?->user;

        if (! $user) {
            return null;
        }

        AccountClaimToken::query()
            ->where('user_id', $user->id)
            ->usable()
            ->update(['used_at' => now()]);

        $plainToken = Str::random(64);
        $claimToken = AccountClaimToken::query()->create([
            'user_id' => $user->id,
            'enrollment_id' => $enrollment->id,
            'email' => $recipientEmail,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(self::CLAIM_LINK_EXPIRATION_HOURS),
            'used_at' => null,
        ]);

        if (! $sendNotification || ! config('services.enrollment_claim_mail.enabled', false)) {
            Log::info('Enrollment claim mail is in draft mode; skipping dispatch.', [
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'recipient_email' => $recipientEmail,
            ]);

            return [
                'token' => $claimToken,
                'plain_token' => $plainToken,
            ];
        }

        Notification::route('mail', $recipientEmail)
            ->notify(new EnrollmentAccountClaimNotification(
                claimUrl: $this->claimUrl($plainToken),
                expiresAtLabel: $claimToken->expires_at?->format('M d, Y h:i A T') ?? '30 days',
                accountEmail: (string) $user->email,
            ));

        return [
            'token' => $claimToken,
            'plain_token' => $plainToken,
        ];
    }

    public function resolveUsableToken(string $plainToken): ?AccountClaimToken
    {
        $accountClaimToken = $this->resolveToken($plainToken);

        if (! $accountClaimToken instanceof AccountClaimToken) {
            return null;
        }

        if (! $accountClaimToken->isUsable()) {
            return null;
        }

        return $accountClaimToken;
    }

    public function resolveToken(string $plainToken): ?AccountClaimToken
    {
        $normalizedToken = trim($plainToken);

        if ($normalizedToken === '') {
            return null;
        }

        return AccountClaimToken::query()
            ->with('user', 'enrollment.student')
            ->where('token_hash', hash('sha256', $normalizedToken))
            ->first();
    }

    public function completeClaim(AccountClaimToken $accountClaimToken, string $password): void
    {
        if (! $accountClaimToken->isUsable()) {
            throw new RuntimeException('This claim token is no longer valid.');
        }

        DB::transaction(function () use ($accountClaimToken, $password): void {
            $accountClaimToken->loadMissing('user');

            $user = $accountClaimToken->user;
            if (! $user) {
                throw new RuntimeException('Unable to resolve account for this claim token.');
            }

            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'password_updated_at' => now(),
            ])->save();

            $accountClaimToken->forceFill([
                'used_at' => now(),
            ])->save();

            AccountClaimToken::query()
                ->where('user_id', $user->id)
                ->whereKeyNot($accountClaimToken->id)
                ->usable()
                ->update(['used_at' => now()]);
        });
    }

    private function claimUrl(string $plainToken): string
    {
        $relativePath = route('account.claim.show', ['token' => $plainToken], false);
        $claimBaseUrl = trim((string) config('services.enrollment_claim_mail.claim_base_url', config('app.url')));

        if ($claimBaseUrl === '') {
            return url($relativePath);
        }

        return rtrim($claimBaseUrl, '/').'/'.ltrim($relativePath, '/');
    }
}
