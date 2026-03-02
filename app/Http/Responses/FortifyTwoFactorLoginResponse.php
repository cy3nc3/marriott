<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\InteractsWithLoginWelcomeToast;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class FortifyTwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use InteractsWithLoginWelcomeToast;

    public function toResponse($request): Response
    {
        $this->flashLoginWelcomeToast($request);

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(Fortify::redirects('login'));
    }
}
