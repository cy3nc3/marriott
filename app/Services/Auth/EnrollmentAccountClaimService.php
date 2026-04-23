<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\AccountClaimToken;
use App\Models\Enrollment;
use App\Models\User;
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

    public function issuePlainTokenForEnrollmentUser(Enrollment $enrollment, User $user): ?string
    {
        $tokenPayload = $this->issueTokenPayloadForEnrollment(
            $enrollment,
            sendNotification: false,
            targetUserId: (int) $user->id
        );

        return $tokenPayload['plain_token'] ?? null;
    }

    /**
     * @return array{token: AccountClaimToken, plain_token: string}|null
     */
    private function issueTokenPayloadForEnrollment(
        Enrollment $enrollment,
        bool $sendNotification,
        ?int $targetUserId = null,
    ): ?array {
        if ((string) $enrollment->status !== 'enrolled') {
            return null;
        }

        $recipientEmail = trim((string) $enrollment->email);
        if ($recipientEmail === '') {
            return null;
        }

        $enrollment->loadMissing('student.user', 'student.parents');
        $parentUsers = $enrollment->student?->parents?->all() ?? [];
        $users = collect([
            $enrollment->student?->user,
            ...$parentUsers,
        ])
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique(fn (User $user): int => (int) $user->id)
            ->values();

        $studentUser = $users->first(function (User $user): bool {
            $role = $user->role;
            $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

            return $roleValue === UserRole::STUDENT->value;
        });
        $parentUser = $users->first(function (User $user): bool {
            $role = $user->role;
            $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

            return $roleValue === UserRole::PARENT->value;
        });

        if (! $studentUser instanceof User || ! $parentUser instanceof User) {
            Log::warning('Enrollment claim tokens were not issued because required accounts are missing.', [
                'enrollment_id' => $enrollment->id,
                'student_user_id' => $studentUser?->id,
                'parent_user_id' => $parentUser?->id,
                'recipient_email' => $recipientEmail,
            ]);

            return null;
        }

        if ($targetUserId !== null) {
            $users = $users->filter(
                fn (User $user): bool => (int) $user->id === $targetUserId
            )->values();
        }

        if ($users->isEmpty()) {
            return null;
        }

        $tokenPayloads = [];

        /** @var User $user */
        foreach ($users as $user) {
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

            $tokenPayloads[] = [
                'token' => $claimToken,
                'plain_token' => $plainToken,
                'user' => $user,
            ];
        }

        if (! $sendNotification || ! config('services.enrollment_claim_mail.enabled', false)) {
            Log::info('Enrollment claim mail is in draft mode; skipping dispatch.', [
                'enrollment_id' => $enrollment->id,
                'user_ids' => $users->pluck('id')->all(),
                'recipient_email' => $recipientEmail,
            ]);

            $selectedPayload = $this->selectPrimaryPayload($tokenPayloads);

            return $selectedPayload ? [
                'token' => $selectedPayload['token'],
                'plain_token' => $selectedPayload['plain_token'],
            ] : null;
        }

        $studentPayload = $this->findFirstPayloadByRole($tokenPayloads, UserRole::STUDENT->value);
        $parentPayload = $this->findFirstPayloadByRole($tokenPayloads, UserRole::PARENT->value);

        if (! $studentPayload || ! $parentPayload) {
            Log::warning('Enrollment claim mail was skipped because both student and parent claim links are required.', [
                'enrollment_id' => $enrollment->id,
                'recipient_email' => $recipientEmail,
            ]);

            return null;
        }

        $fallbackPayload = $this->selectPrimaryPayload($tokenPayloads);
        $expiresAtLabel = ($fallbackPayload['token'] ?? null)?->expires_at?->format('M d, Y h:i A T') ?? '30 days';

        Notification::route('mail', $recipientEmail)
            ->notify(new EnrollmentAccountClaimNotification(
                studentClaimUrl: $this->claimUrl($studentPayload['plain_token']),
                parentClaimUrl: $this->claimUrl($parentPayload['plain_token']),
                expiresAtLabel: $expiresAtLabel,
            ));

        $selectedPayload = $this->selectPrimaryPayload($tokenPayloads);

        return $selectedPayload ? [
            'token' => $selectedPayload['token'],
            'plain_token' => $selectedPayload['plain_token'],
        ] : null;
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

    /**
     * @param  list<array{token: AccountClaimToken, plain_token: string, user: User}>  $tokenPayloads
     * @return array{token: AccountClaimToken, plain_token: string, user: User}|null
     */
    private function selectPrimaryPayload(array $tokenPayloads): ?array
    {
        if ($tokenPayloads === []) {
            return null;
        }

        usort($tokenPayloads, function (array $left, array $right): int {
            return strcmp(
                (string) ($left['user']->email ?? ''),
                (string) ($right['user']->email ?? '')
            );
        });

        return $tokenPayloads[0] ?? null;
    }

    /**
     * @param  list<array{token: AccountClaimToken, plain_token: string, user: User}>  $tokenPayloads
     * @return array{token: AccountClaimToken, plain_token: string, user: User}|null
     */
    private function findFirstPayloadByRole(array $tokenPayloads, string $role): ?array
    {
        foreach ($tokenPayloads as $payload) {
            $userRole = $payload['user']->role ?? null;
            $userRoleValue = $userRole instanceof UserRole
                ? $userRole->value
                : (string) $userRole;

            if ($userRoleValue === $role) {
                return $payload;
            }
        }

        return null;
    }
}
