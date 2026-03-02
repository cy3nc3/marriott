<?php

namespace App\Http\Responses\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;

trait InteractsWithLoginWelcomeToast
{
    protected function flashLoginWelcomeToast($request): void
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return;
        }

        $firstName = trim((string) $user->first_name);

        if ($firstName === '') {
            $firstName = trim(Str::before((string) $user->name, ' '));
        }

        if ($firstName === '') {
            $firstName = 'User';
        }

        $role = $user->role;
        $roleValue = is_string($role) ? $role : $role?->value;
        $roleLabel = UserRole::tryFrom((string) $roleValue)?->label()
            ?? Str::of((string) $roleValue)->replace('_', ' ')->title()->toString();

        $request->session()->flash('login_welcome_toast', [
            'key' => Str::uuid()->toString(),
            'title' => "Welcome, {$firstName}!",
            'description' => "Logged in as {$roleLabel}",
        ]);
    }
}
