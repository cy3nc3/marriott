<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\InteractsWithLoginWelcomeToast;
use App\Models\User;
use App\Services\Auth\SavedAccountLoginManager;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class FortifyTwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use InteractsWithLoginWelcomeToast;

    public function __construct(
        private SavedAccountLoginManager $savedAccountLoginManager,
    ) {}

    public function toResponse($request): Response
    {
        $this->flashLoginWelcomeToast($request);
        $this->flashSavedAccountLogin($request);

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(Fortify::redirects('login'));
    }

    private function flashSavedAccountLogin($request): void
    {
        $user = $request->user();
        $deviceId = trim((string) $request->input('saved_account_device_id'));

        if (! $user instanceof User || $deviceId === '') {
            return;
        }

        if ($request->boolean('remember')) {
            $request->session()->flash(
                'saved_account_login',
                $this->savedAccountLoginManager->issueForUser($user, $deviceId),
            );

            return;
        }

        $this->savedAccountLoginManager->revokeForUser($user, $deviceId);

        $request->session()->flash(
            'saved_account_login',
            $this->savedAccountLoginManager->buildForgetPayload(
                (string) $user->email,
                $deviceId,
            ),
        );
    }
}
