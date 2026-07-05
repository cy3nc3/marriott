<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\AccountClaimToken;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentSingleAccountClaimNotification;
use App\Notifications\StaffAccountClaimNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

class EnrollmentAccountClaimService
{
    private const CLAIM_LINK_EXPIRATION_HOURS = 24 * 30;
    private const CLAIM_EMAIL_TIMEZONE = 'Asia/Manila';

    public function issueForEnrollment(Enrollment $enrollment): ?AccountClaimToken
    {
        $tokenPayload = $this->issueTokenPayloadForEnrollment($enrollment, sendNotification: true);

        return $tokenPayload['token'] ?? null;
    }

    public function issueForEnrollmentUser(Enrollment $enrollment, User $user): ?AccountClaimToken
    {
        $tokenPayload = $this->issueTokenPayloadForEnrollment(
            $enrollment,
            sendNotification: true,
            targetUserId: (int) $user->id
        );

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

    public function issueForStaffUser(User $user, string $recipientEmail): ?AccountClaimToken
    {
        $normalizedRecipientEmail = trim(Str::lower($recipientEmail));

        if ($normalizedRecipientEmail === '') {
            return null;
        }

        AccountClaimToken::query()
            ->where('user_id', $user->id)
            ->usable()
            ->update(['used_at' => now()]);

        $plainToken = Str::random(64);
        $claimToken = AccountClaimToken::query()->create([
            'user_id' => $user->id,
            'enrollment_id' => null,
            'email' => $normalizedRecipientEmail,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(self::CLAIM_LINK_EXPIRATION_HOURS),
            'used_at' => null,
        ]);

        if (! config('services.enrollment_claim_mail.enabled', false)) {
            Log::info('Staff account claim mail is in draft mode; skipping dispatch.', [
                'user_id' => $user->id,
                'recipient_email' => $normalizedRecipientEmail,
            ]);

            return $claimToken;
        }

        Notification::route('mail', $normalizedRecipientEmail)
            ->notify(new StaffAccountClaimNotification(
                accountName: (string) $user->name,
                accountEmail: (string) $user->email,
                claimUrl: $this->claimUrl($plainToken),
                expiresAtLabel: $this->formatClaimExpiresAt($claimToken),
            ));

        return $claimToken;
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
                'guardian_contact_email' => $enrollment->email,
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
            $recipientEmail = $this->resolveEnrollmentClaimRecipientEmail($user, $enrollment);
            if ($recipientEmail === null) {
                continue;
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

            $tokenPayloads[] = [
                'token' => $claimToken,
                'plain_token' => $plainToken,
                'user' => $user,
                'recipient_email' => $recipientEmail,
            ];
        }

        if ($tokenPayloads === []) {
            Log::warning('Enrollment claim tokens were not issued because no claim recipient email was available.', [
                'enrollment_id' => $enrollment->id,
                'guardian_contact_email' => $enrollment->email,
            ]);

            return null;
        }

        if (! $sendNotification || ! config('services.enrollment_claim_mail.enabled', false)) {
            Log::info('Enrollment claim mail is in draft mode; skipping dispatch.', [
                'enrollment_id' => $enrollment->id,
                'user_ids' => $users->pluck('id')->all(),
                'recipient_emails' => collect($tokenPayloads)->pluck('recipient_email')->unique()->values()->all(),
            ]);

            $selectedPayload = $this->selectPrimaryPayload($tokenPayloads);

            return $selectedPayload ? [
                'token' => $selectedPayload['token'],
                'plain_token' => $selectedPayload['plain_token'],
            ] : null;
        }

        $fallbackPayload = $this->selectPrimaryPayload($tokenPayloads);
        $expiresAtLabel = isset($fallbackPayload['token'])
            ? $this->formatClaimExpiresAt($fallbackPayload['token'])
            : '30 days';

        foreach ($tokenPayloads as $payload) {
            Notification::route('mail', $payload['recipient_email'])
                ->notify(new EnrollmentSingleAccountClaimNotification(
                    accountLabel: $this->claimAccountLabel($payload['user']),
                    claimUrl: $this->claimUrl($payload['plain_token']),
                    expiresAtLabel: $expiresAtLabel,
                ));
        }

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

    private function formatClaimExpiresAt(AccountClaimToken $claimToken): string
    {
        if (! $claimToken->expires_at) {
            return '30 days';
        }

        return $claimToken->expires_at
            ->copy()
            ->timezone(self::CLAIM_EMAIL_TIMEZONE)
            ->format('M d, Y h:i A').' PHT';
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

    private function resolveEnrollmentClaimRecipientEmail(User $user, Enrollment $enrollment): ?string
    {
        $role = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;
        $guardianContactEmail = $this->normalizeEmail($enrollment->email);

        if ($role === UserRole::STUDENT->value) {
            return $this->normalizeEmail($user->personal_email) ?? $guardianContactEmail;
        }

        if ($role === UserRole::PARENT->value) {
            return $guardianContactEmail;
        }

        return null;
    }

    private function claimAccountLabel(User $user): string
    {
        $role = $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;

        return $role === UserRole::PARENT->value ? 'Parent' : 'Student';
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $normalizedEmail = Str::lower(trim((string) $email));

        return $normalizedEmail !== '' ? $normalizedEmail : null;
    }
}
