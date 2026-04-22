# Email Demo Delivery Plan (Enrollment -> Email Sent)

## Current Implementation Status (2026-04-22)

- Implemented: enrollment-to-claim-email flow using Resend + queued notification.
- Implemented: claim page now requires phone verification before password setup.
- Implemented: Firebase SMS OTP verification on claim flow.
- Implemented: expired claim-link recovery path after successful phone verification (new token issued).
- Implemented: mobile number normalization to canonical `+639XXXXXXXXX`.
- Implemented: claim-link base URL configuration via `ENROLLMENT_CLAIM_BASE_URL`.
- Draft remains: production-grade mail inbox/routing hardening and final DNS/mail policy rollout.

## Objective

Enable the system to send account claim/credential instructions to the email provided during enrollment, using a free setup suitable for capstone demonstration.

## Important Constraints

- DigitalOcean blocks outbound SMTP ports `25`, `465`, and `587` by default on new Droplets.
- Sending service and inbox hosting are different:
  - Sending service: delivers outgoing emails.
  - Inbox hosting/forwarding: lets you receive/read inbound emails to custom addresses.

## Recommended Free Demo Architecture

1. Outbound email sending:
- Use `Resend` free plan (API-based, avoids SMTP port blocking).
- Alternative: `Brevo` SMTP on port `2525` if you prefer SMTP.

2. Inbound viewing for system-created addresses:
- Use `Cloudflare Email Routing` (free) on a **separate demo subdomain** (for example: `demo-mail.msqc.tech`) to avoid disrupting current school Google Workspace routing.
- Forward inbound emails to a real mailbox you already control (for example Gmail).

3. Enrollment-triggered email:
- When enrollment is approved/created, generate a claim token and send an email to the enrollment email address.
- Email should contain a secure claim link (recommended), not plaintext password.

## Enrollment Email Flow (Recommended)

1. User enrolls and provides email.
2. System creates/links a user account in pending state.
3. System generates claim token with expiry (for example 24 hours).
4. System queues a `SendEnrollmentAccountClaimEmail` job/notification.
5. Recipient opens email and clicks claim link.
6. Recipient sets password and activates account.
7. System marks token as used and logs audit event.

## Implemented Claim + OTP Flow

1. Enrollment reaches `enrolled` status.
2. System issues a claim token and queues claim email notification.
3. Recipient opens claim link.
4. Recipient sees redacted enrolled mobile (`+63 9*****####`).
5. Recipient enters mobile using fixed `+63` prefix input (`9XXXXXXXXX`).
6. Backend validates entered phone against enrollment record.
7. Firebase sends OTP.
8. Recipient verifies OTP.
9. Recipient sets password.
10. Token is consumed and cannot be reused.

## Mobile Number Normalization Rule

- Canonical persisted format: `+639XXXXXXXXX`.
- Accepted enrollment/claim inputs are normalized to canonical format.
- UI input pattern for enrollment and claim:
  - prefix: fixed `+63` (not editable)
  - user input: subscriber number only (`9XXXXXXXXX`)

## Implementation Checklist (Laravel)

1. Data model
- Ensure enrollment record has email field.
- Add/confirm table for account claim tokens:
  - `user_id`
  - `email`
  - `token` (hashed)
  - `expires_at`
  - `used_at`

2. Domain logic
- Add service method (for example `EnrollmentAccountClaimService`) to:
  - create token
  - invalidate old tokens
  - dispatch mail notification

3. Mail content
- Subject: `Your MarriottConnect account is ready`
- Body:
  - greeting
  - claim link
  - expiration note
  - support contact

4. Queue
- Send mail via queue (`ShouldQueue`) to avoid blocking enrollment flow.
- Ensure worker is running in production.

5. Security
- Do not email plaintext passwords.
- Use signed/hashed token and short expiry.
- Rate-limit claim attempts.

## Environment Variables Added/Used

```env
ENROLLMENT_CLAIM_MAIL_ENABLED=true
ENROLLMENT_CLAIM_SMS_ENABLED=true
ENROLLMENT_CLAIM_BASE_URL=http://127.0.0.1:8001
MAIL_MAILER=resend
RESEND_API_KEY=re_xxx
FIREBASE_API_KEY=AIza...
VITE_FIREBASE_API_KEY=AIza...
VITE_FIREBASE_AUTH_DOMAIN=...
VITE_FIREBASE_PROJECT_ID=...
VITE_FIREBASE_STORAGE_BUCKET=...
VITE_FIREBASE_MESSAGING_SENDER_ID=...
VITE_FIREBASE_APP_ID=...
```

## Provider Setup Option A (Preferred): Resend

1. Create Resend account (free plan).
2. Add sending domain or subdomain (recommended: `mail.msqc.tech` or `demo-mail.msqc.tech`).
3. Add required DNS records in Cloudflare (SPF/DKIM from Resend dashboard).
4. Generate API key.
5. Configure Laravel:

```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=no-reply@demo-mail.msqc.tech
MAIL_FROM_NAME="MarriottConnect"
RESEND_KEY=re_xxxxxxxxxxxxxxxxx
```

## Provider Setup Option B: Brevo SMTP (Port 2525)

1. Create Brevo account (free plan).
2. Verify sender/domain in Brevo.
3. Configure Laravel SMTP using port `2525`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=2525
MAIL_USERNAME=your_brevo_login
MAIL_PASSWORD=your_brevo_smtp_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@demo-mail.msqc.tech
MAIL_FROM_NAME="MarriottConnect"
```

## Inbound Routing (Read Emails Sent to Demo Addresses)

1. In Cloudflare, enable Email Routing for `demo-mail.msqc.tech` (or chosen demo domain/subdomain).
2. Create route:
- `*@demo-mail.msqc.tech` -> your real inbox (for example `yourdemo@gmail.com`)
3. Verify destination mailbox via Cloudflare confirmation email.

This allows you to demonstrate that external recipients can receive system emails without running your own mail server.

## Enrollment Feature Acceptance Criteria

- On enrollment approval, email is sent to provided address.
- Recipient receives claim email within acceptable delay (queue + provider latency).
- Claim link works once; expired links can be refreshed after successful phone verification.
- Audit trail records email dispatch and claim completion.

## Demo Test Script

1. Use a fresh enrollment email (external inbox).
2. Approve enrollment in system.
3. Confirm queued job processed.
4. Confirm email delivered to recipient inbox.
5. Open claim link and set password.
6. Log in with new account.
7. Confirm token cannot be reused.

## Operational Notes

- Keep this setup for demo/temporary production.
- For long-term school deployment, coordinate with official Google Workspace mail policy and domain admins.
