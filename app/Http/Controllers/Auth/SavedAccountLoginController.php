<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreSavedAccountLoginRequest;
use App\Http\Responses\Concerns\InteractsWithLoginWelcomeToast;
use App\Models\User;
use App\Services\Auth\SavedAccountLoginManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class SavedAccountLoginController extends Controller
{
    use InteractsWithLoginWelcomeToast;

    public function __construct(
        private StatefulGuard $guard,
        private SavedAccountLoginManager $savedAccountLoginManager,
    ) {}

    public function store(StoreSavedAccountLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->savedAccountLoginManager->verifyCredentials(
            (string) $validated['email'],
            (string) $validated['device_id'],
            (string) $validated['selector'],
            (string) $validated['token'],
        );

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->guard->login($user, true);
        $request->session()->regenerate();
        $this->flashLoginWelcomeToast($request);
        $request->session()->flash(
            'saved_account_login',
            $this->savedAccountLoginManager->issueForUser(
                $user,
                (string) $validated['device_id'],
            ),
        );

        return redirect()->intended(Fortify::redirects('login'));
    }
}
