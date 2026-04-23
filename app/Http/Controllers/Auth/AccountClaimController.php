<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendAccountClaimOtpRequest;
use App\Http\Requests\Auth\StoreAccountClaimPasswordRequest;
use App\Http\Requests\Auth\VerifyAccountClaimOtpRequest;
use App\Services\Auth\AccountClaimPhoneOtpService;
use App\Services\Auth\EnrollmentAccountClaimService;
use App\Services\Auth\FirebasePhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AccountClaimController extends Controller
{
    public function show(string $token, EnrollmentAccountClaimService $claimService): Response|RedirectResponse
    {
        $accountClaimToken = $claimService->resolveToken($token);

        if (! $accountClaimToken) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'This claim link is invalid or has expired.',
                ]);
        }

        $phoneVerified = $this->isPhoneVerified($accountClaimToken->id);
        $phoneNumber = (string) ($accountClaimToken->enrollment?->student?->contact_number ?? '');
        $tokenAlreadyUsed = $accountClaimToken->isUsed();

        return Inertia::render('auth/claim-account', [
            'token' => $token,
            'account_email' => (string) ($accountClaimToken->user?->email ?? ''),
            'phone_number_redacted' => $this->redactPhoneNumber($phoneNumber),
            'phone_verified' => $tokenAlreadyUsed ? true : $phoneVerified,
            'is_expired' => $tokenAlreadyUsed ? false : ! $accountClaimToken->isUsable(),
            'claim_completed' => $tokenAlreadyUsed || (bool) session('claim_completed', false),
            'login_url' => route('login'),
        ]);
    }

    public function sendOtp(
        SendAccountClaimOtpRequest $request,
        string $token,
        EnrollmentAccountClaimService $claimService,
        AccountClaimPhoneOtpService $otpService,
    ): JsonResponse {
        $accountClaimToken = $claimService->resolveToken($token);

        if (! $accountClaimToken || $accountClaimToken->isUsed()) {
            return response()->json([
                'message' => 'This claim link is invalid or has expired.',
                'errors' => [
                    'token' => ['This claim link is invalid or has expired.'],
                ],
            ], 422);
        }

        try {
            $otpService->assertPhoneMatches($accountClaimToken, (string) $request->input('phone_number'));
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'phone_number' => [$exception->getMessage()],
                ],
            ], 422);
        }

        if (! config('services.enrollment_claim_sms.enabled', false)) {
            return response()->json([
                'message' => 'SMS delivery is in draft mode. Enable ENROLLMENT_CLAIM_SMS_ENABLED for real sends.',
                'errors' => [
                    'phone_number' => ['SMS verification is not enabled.'],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Phone verified. Proceed with OTP send.',
        ]);
    }

    public function verifyOtp(
        VerifyAccountClaimOtpRequest $request,
        string $token,
        EnrollmentAccountClaimService $claimService,
        AccountClaimPhoneOtpService $otpService,
        FirebasePhoneVerificationService $firebasePhoneVerificationService,
    ): RedirectResponse {
        $accountClaimToken = $claimService->resolveToken($token);

        if (! $accountClaimToken || $accountClaimToken->isUsed()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'This claim link is invalid or has expired.',
                ]);
        }

        if (! config('services.enrollment_claim_sms.enabled', false)) {
            return back()->withErrors([
                'id_token' => 'SMS verification is not enabled.',
            ]);
        }

        try {
            $verifiedPhone = $firebasePhoneVerificationService
                ->resolveVerifiedPhoneNumber((string) $request->input('id_token'));
            $otpService->recordVerifiedPhone($accountClaimToken, $verifiedPhone);
        } catch (RuntimeException $exception) {
            Log::warning('Firebase phone verification failed.', [
                'claim_token_id' => $accountClaimToken->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'id_token' => 'Invalid or expired OTP code.',
            ]);
        }

        if ($accountClaimToken->isUsable()) {
            $this->markPhoneVerified($accountClaimToken->id);

            return back()->with('status', 'Phone verified. You can now set your password.');
        }

        $enrollment = $accountClaimToken->enrollment;
        if (! $enrollment) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'Unable to issue a new claim link at this time.',
                ]);
        }

        $claimUser = $accountClaimToken->user;
        if (! $claimUser) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'Unable to issue a new claim link at this time.',
                ]);
        }

        $replacementPlainToken = $claimService->issuePlainTokenForEnrollmentUser($enrollment, $claimUser);
        if (! $replacementPlainToken) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'Unable to issue a new claim link at this time.',
                ]);
        }

        $replacementToken = $claimService->resolveUsableToken($replacementPlainToken);
        if (! $replacementToken) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'Unable to issue a new claim link at this time.',
                ]);
        }

        $this->markPhoneVerified($replacementToken->id);

        return redirect()
            ->route('account.claim.show', ['token' => $replacementPlainToken])
            ->with('status', 'Expired link refreshed. You can now set your password.');
    }

    public function store(
        StoreAccountClaimPasswordRequest $request,
        string $token,
        EnrollmentAccountClaimService $claimService
    ): RedirectResponse {
        $accountClaimToken = $claimService->resolveUsableToken($token);

        if (! $accountClaimToken) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'token' => 'This claim link is invalid or has expired.',
                ]);
        }

        if (! $this->isPhoneVerified($accountClaimToken->id)) {
            return back()->withErrors([
                'token' => 'Verify your phone number first to continue.',
            ]);
        }

        $claimService->completeClaim($accountClaimToken, (string) $request->input('password'));
        session()->forget($this->phoneVerifiedSessionKey($accountClaimToken->id));

        return redirect()
            ->route('account.claim.show', ['token' => $token])
            ->with('claim_completed', true)
            ->with('status', 'Password has been set. Keep your account email and password for your next login.');
    }

    private function markPhoneVerified(int $claimTokenId): void
    {
        if ($claimTokenId > 0) {
            session()->put($this->phoneVerifiedSessionKey($claimTokenId), true);
        }
    }

    private function isPhoneVerified(int $claimTokenId): bool
    {
        return (bool) session()->get($this->phoneVerifiedSessionKey($claimTokenId), false);
    }

    private function phoneVerifiedSessionKey(int $claimTokenId): string
    {
        return 'account_claim_phone_verified_'.$claimTokenId;
    }

    private function redactPhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            $digits = '63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '63'.$digits;
        }

        if (! str_starts_with($digits, '63') || strlen($digits) !== 12) {
            return 'Unavailable';
        }

        $subscriber = substr($digits, 2);

        return '+63 '.substr($subscriber, 0, 1).'*****'.substr($subscriber, -4);
    }
}
