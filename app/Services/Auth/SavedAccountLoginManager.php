<?php

namespace App\Services\Auth;

use App\Models\SavedAccountLogin;
use App\Models\User;
use Illuminate\Support\Str;

class SavedAccountLoginManager
{
    public function issueForUser(User $user, string $deviceId): array
    {
        $normalizedDeviceId = trim($deviceId);
        $selector = (string) Str::uuid();
        $plainToken = Str::random(64);
        $expiresAt = now()->addDays(30);

        SavedAccountLogin::query()
            ->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $normalizedDeviceId,
                ],
                [
                    'selector' => $selector,
                    'token_hash' => hash('sha256', $plainToken),
                    'expires_at' => $expiresAt,
                    'last_used_at' => now(),
                ],
            );

        return [
            'action' => 'store',
            'account' => [
                'email' => (string) $user->email,
                'remember' => true,
                'last_used_at' => now()->toIso8601String(),
                'device_login' => [
                    'device_id' => $normalizedDeviceId,
                    'selector' => $selector,
                    'token' => $plainToken,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ],
        ];
    }

    public function revokeForUser(User $user, string $deviceId): void
    {
        $normalizedDeviceId = trim($deviceId);

        if ($normalizedDeviceId === '') {
            return;
        }

        SavedAccountLogin::query()
            ->where('user_id', $user->id)
            ->where('device_id', $normalizedDeviceId)
            ->delete();
    }

    public function verifyCredentials(
        string $email,
        string $deviceId,
        string $selector,
        string $token,
    ): ?User {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user instanceof User) {
            return null;
        }

        if (! $user->is_active) {
            return null;
        }

        if ($user->access_expires_at && now()->greaterThanOrEqualTo($user->access_expires_at)) {
            return null;
        }

        $savedAccountLogin = SavedAccountLogin::query()
            ->where('user_id', $user->id)
            ->where('device_id', trim($deviceId))
            ->where('selector', trim($selector))
            ->first();

        if (! $savedAccountLogin instanceof SavedAccountLogin) {
            return null;
        }

        if ($savedAccountLogin->isExpired()) {
            $savedAccountLogin->delete();

            return null;
        }

        if (! hash_equals($savedAccountLogin->token_hash, hash('sha256', $token))) {
            return null;
        }

        $savedAccountLogin->forceFill([
            'last_used_at' => now(),
        ])->save();

        return $user;
    }

    public function buildForgetPayload(string $email, string $deviceId): array
    {
        return [
            'action' => 'forget',
            'email' => $email,
            'device_id' => trim($deviceId),
        ];
    }
}
