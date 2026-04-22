# Account Claim Flow Implementation Notes (2026-04-22)

## Summary

This document captures the implemented behavior for enrollment-triggered account claim, OTP verification, and mobile-number normalization.

## Implemented Flow

1. Registrar creates/updates enrollment and stores guardian mobile in canonical format: `+639XXXXXXXXX`.
2. Finance transition to `enrolled` issues an account-claim token and queues claim email.
3. Claim email contains a claim link.
4. User opens claim page and sees a redacted enrolled number format: `+63 9*****1567`.
5. User enters phone in fixed-prefix input (`+63` locked, user types `9XXXXXXXXX`).
6. Backend validates entered number against enrollment record.
7. Firebase sends OTP to validated number.
8. User verifies OTP.
9. User sets password.
10. Claim token is marked used and account becomes usable for login.

## Mobile Number Standard

- Canonical stored format: `+639XXXXXXXXX`.
- Enrollment UI: fixed `+63` prefix, subscriber input only (`9XXXXXXXXX`).
- Claim UI: same fixed `+63` pattern.
- Backend normalization accepts common PH inputs and converts to canonical:
  - `09XXXXXXXXX`
  - `9XXXXXXXXX`
  - `639XXXXXXXXX`
  - `+639XXXXXXXXX`

## Security And Control Notes

- Claim completion still requires valid token + verified phone session.
- OTP send endpoint keeps token/phone validation and throttle.
- OTP send endpoint is excluded from CSRF validation to avoid local/client mismatch issues in this flow.
- OTP verify and password set endpoints remain protected by normal app security checks.

## Local Testing Notes

- Required runtime:
  - Laravel app server
  - queue worker (`php artisan queue:work`)
  - frontend dev/build assets
- For Firebase Phone Auth:
  - add local hosts to Firebase Authorized Domains (`localhost`, `127.0.0.1`)
  - ensure Phone provider is enabled
- Claim links are generated from `ENROLLMENT_CLAIM_BASE_URL` (fallback `APP_URL`).

## Key Config Flags

- `ENROLLMENT_CLAIM_MAIL_ENABLED=true` to enable claim emails.
- `ENROLLMENT_CLAIM_SMS_ENABLED=true` to enable phone OTP path.
- `ENROLLMENT_CLAIM_BASE_URL` for claim-link base URL.
- `MAIL_MAILER=resend` with valid `RESEND_API_KEY`.
- `FIREBASE_API_KEY` and matching `VITE_FIREBASE_*` values.
