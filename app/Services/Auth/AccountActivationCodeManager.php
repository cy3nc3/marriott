<?php

namespace App\Services\Auth;

use App\Models\AccountActivationCode;
use App\Models\User;
use Illuminate\Support\Str;

class AccountActivationCodeManager
{
    private const ACTIVATION_CODE_LENGTH = 10;

    public function issueForUser(User $user): string
    {
        $plainCode = $this->generateCode();

        AccountActivationCode::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => hash('sha256', $plainCode),
                'expires_at' => now()->addDays(7),
                'used_at' => null,
            ],
        );

        return $plainCode;
    }

    public function consumeIfValid(User $user, string $plainCode): bool
    {
        $normalizedCode = trim($plainCode);

        if ($normalizedCode === '') {
            return false;
        }

        $activationCode = AccountActivationCode::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $activationCode instanceof AccountActivationCode) {
            return false;
        }

        if (! $activationCode->isUsable()) {
            return false;
        }

        if (! hash_equals($activationCode->code_hash, hash('sha256', $normalizedCode))) {
            return false;
        }

        $activationCode->forceFill([
            'used_at' => now(),
        ])->save();

        return true;
    }

    private function generateCode(): string
    {
        $rawToken = strtoupper(Str::random(18));
        $sanitized = str_replace(['I', 'L', 'O'], ['8', '7', '0'], $rawToken);

        return substr($sanitized, 0, self::ACTIVATION_CODE_LENGTH);
    }
}
