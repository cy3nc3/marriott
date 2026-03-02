<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\InteractsWithLoginWelcomeToast;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class FortifyLoginResponse implements LoginResponseContract
{
    use InteractsWithLoginWelcomeToast;

    public function toResponse($request): Response
    {
        $this->flashLoginWelcomeToast($request);

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(Fortify::redirects('login'));
    }
}
