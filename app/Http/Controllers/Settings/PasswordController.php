<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class PasswordController extends Controller
{
    /**
     * Show the user's security settings page (Password + 2FA + Sessions).
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/password', [
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'sessions' => app(SessionController::class)->index($request),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $isForcedPasswordChange = (bool) $user->must_change_password;

        $user->update([
            'password' => $request->password,
            'must_change_password' => false,
            'password_updated_at' => now(),
        ]);

        if ($isForcedPasswordChange) {
            return redirect()->route('dashboard');
        }

        return back();
    }
}
