# Account Claim Flow Smoke-Test Checklist

Use this checklist after local setup to validate enrollment email dispatch and claim completion end-to-end.

## Preconditions

1. App server running (`php artisan serve --port=8001`).
2. Queue worker running (`php artisan queue:work`).
3. Frontend dev server running (`npm run dev`), or built assets available.
4. `.env` has:
   - `MAIL_MAILER=resend`
   - `ENROLLMENT_CLAIM_MAIL_ENABLED=true`
   - `ENROLLMENT_CLAIM_SMS_ENABLED=true`
   - `ENROLLMENT_CLAIM_BASE_URL=http://127.0.0.1:8001` (or your active host)
   - `RESEND_API_KEY` set
   - `FIREBASE_API_KEY` + `VITE_FIREBASE_*` set
5. Firebase Auth:
   - Phone provider enabled
   - Authorized domains include current host (`localhost`, `127.0.0.1`)

## Test Case A: Enrollment -> Claim Email Dispatch

1. Create/prepare enrollment with:
   - status not yet `enrolled`
   - valid email
   - valid PH mobile in expected format path
2. Trigger finance transition to `enrolled`.

Expected:
1. New row in `account_claim_tokens` with:
   - matching `enrollment_id`
   - matching `email`
   - `used_at = null`
   - `expires_at` populated
2. Email notification job is dispatched and processed by queue worker.
3. Recipient inbox gets message with subject:
   - `Your MarriottConnect account is ready`
4. Email action link points to:
   - `/account/claim/{token}`
   - base URL from `ENROLLMENT_CLAIM_BASE_URL` (or `APP_URL` fallback)

Verification commands:

```bash
php artisan tinker --execute "dump(\App\Models\AccountClaimToken::query()->latest()->first()?->only(['id','enrollment_id','email','expires_at','used_at']));"
```

## Test Case B: Claim Link Open

1. Open the claim URL from email.

Expected:
1. Claim page loads (Inertia `auth/claim-account`).
2. Redacted phone is shown.
3. If token invalid/expired/used, app redirects to login with token error.

## Test Case C: OTP Send

1. Enter enrolled number using `+63` locked input + subscriber digits (`9XXXXXXXXX`).
2. Click `Verify phone and send OTP`.

Expected:
1. Backend endpoint `/account/claim/{token}/otp/send` returns success.
2. Firebase SMS is sent.
3. UI shows OTP prompt.

Failure diagnostics:
1. `auth/unauthorized-domain`: add current host to Firebase Authorized domains.
2. `auth/operation-not-allowed`: enable Firebase Phone auth provider.
3. `auth/captcha-check-failed`: reload, disable blockers, verify domain config.

## Test Case D: OTP Verify

1. Enter received OTP.
2. Submit verification.

Expected:
1. Firebase ID token validates server-side.
2. Session marks phone as verified for current claim token.
3. UI allows password set section.

## Test Case E: Password Set + Claim Completion

1. Enter password + confirmation.
2. Submit.

Expected:
1. User password updates.
2. `must_change_password` becomes `false`.
3. Current claim token `used_at` becomes non-null.
4. Other usable tokens for same user are invalidated (`used_at` set).
5. Redirect to login with success message.

Verification command:

```bash
php artisan tinker --execute "dump(\App\Models\AccountClaimToken::query()->latest()->first()?->only(['id','used_at']));"
```

## Test Case F: One-Time Token Behavior

1. Re-open previously used claim link.

Expected:
1. Link is rejected.
2. Redirect to login with `already used` or invalid/expired message.

## Runtime Logs To Watch

1. Queue worker terminal:
   - notification/job processing entries
   - mail transport errors
2. Laravel logs (`storage/logs/laravel.log`):
   - Firebase verification failures
   - mail/send exceptions

## Common Failure Checks

1. No email arrives:
   - confirm queue worker is running
   - confirm Resend domain/sender verification
   - confirm `RESEND_API_KEY` is valid
2. OTP blocked:
   - confirm Firebase Phone auth enabled
   - confirm authorized domain setup
3. Token issues:
   - confirm server time/timezone is correct
   - inspect `expires_at` vs current time
