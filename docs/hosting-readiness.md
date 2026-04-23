# Hosting Readiness Checklist

This file tracks decisions and tasks needed before hosting MarriottConnect. Update each item as decisions are made or work is completed.

## Status Legend

- `[ ]` Not started
- `[/]` In progress
- `[x]` Done
- `[?]` Needs decision
- `[!]` Blocked or risky

## Hosting Decisions

- `[x]` Hosting platform: DigitalOcean
  - Reason: available GitHub Student Developer Pack credits make it the practical first production host.
  - Target shape: VPS or managed service that supports PHP-FPM, queue workers, cron, writable storage, and scripted deployments.
- `[x]` Domain name: `msqc.tech`
- `[x]` Domain registrar / DNS strategy: keep registrar at `get.tech`, move DNS to Cloudflare
  - Decision: DNS management/TLS/proxying will be handled in Cloudflare; registrar transfer is not required for capstone deployment.
- `[x]` SSL/TLS provider: Cloudflare
  - Plan: use Cloudflare for DNS, proxying, and TLS in front of DigitalOcean.
- `[x]` Database engine: PostgreSQL (DigitalOcean Managed PostgreSQL)
  - Reason: easier operations (managed backups/patching), cleaner isolation from app runtime, and simpler scaling path.
  - Initial target: smallest managed node in `sgp1`, then scale vertically based on real production metrics.
- `[x]` Mail provider: Resend + Cloudflare Email Routing (demo inbox strategy)
  - Needed for password reset, account verification, reminders, and production notifications.
  - Constraint: do not plan on self-hosted SMTP from the droplet; use a third-party mail provider/API.
  - Outbound mail: Resend (`MAIL_MAILER=resend`) for transactional sends.
  - Inbox strategy for capstone demo: route system-created addresses through Cloudflare Email Routing to a demo Gmail inbox.
  - Temporary app-level fallback implemented: set `DEMO_MAIL_REDIRECT_TO` to force all outbound mail to one demo inbox while account addresses remain domain-realistic.
  - Note: this is a demo-friendly forwarding setup, not full mailbox hosting per account.
  - Reference: `docs/email-demo-delivery-plan.md`.
- `[x]` Backup storage strategy: Spaces-based off-server backups
  - Decision: keep operational backups on server as needed, but copy/store backup artifacts in the provisioned DigitalOcean Spaces bucket.
- `[x]` Deployment method: single-environment manual SSH deployment
  - Plan: deploy one hosted environment on `msqc.tech` for capstone/demo use.
  - Note: reset or reseed demo data as needed before final presentation.
- `[x]` DigitalOcean product choice: Droplet
  - Reason: this Laravel app needs queue workers, scheduled commands, writable local storage, spreadsheet temp files, local backups, and attachment/avatar file handling that fit a VM better than App Platform.
  - Rejected option: App Platform is convenient, but its filesystem is ephemeral and not suitable for the app's current local-storage patterns without additional refactoring to Spaces/object storage and managed background process configuration.

## DigitalOcean Stack And Cost Planning

### Planned Core Architecture

- `[x]` App compute: `1x Basic Droplet` (Ubuntu LTS)
- `[x]` Private networking: `1x VPC` (free)
- `[x]` Network security: `Cloud Firewall` (free)
- `[x]` SSL/TLS + DNS: `Cloudflare` (free plan)
- `[x]` Backups: enable Droplet backups (weekly 20% or daily 30% of Droplet price)
- `[x]` Monitoring: DigitalOcean Monitoring + alerts (free)
- `[x]` Billing safety: configure billing alert threshold

### Optional Services (Choose Based On Requirements)

- `[x]` Managed PostgreSQL (provisioned)
- `[x]` Spaces object storage (provisioned)
- `[?]` Regional Load Balancer (only needed when running multiple app droplets)
- `[?]` Uptime checks (optional external health checks)

### Droplet Size Candidates

- `[ ]` 2 GiB / 2 vCPU Basic: `$18/mo`
- `[x]` 4 GiB / 2 vCPU Basic: `$24/mo` (recommended default starting size)
- `[ ]` 8 GiB / 4 vCPU Basic: `$48/mo`

### Service Price Reference (For Budgeting)

- Droplet backups: `20% weekly` or `30% daily` of droplet price
- Managed PostgreSQL: starts at `~$15.15/mo` (1 GiB / 1 vCPU)
- Spaces: `$5/mo` includes `250 GiB` storage + `1,024 GiB` outbound transfer
- Load Balancer (regional HTTP): `$12/mo per node`
- Volumes block storage: `$0.10/GiB/mo`
- Snapshots: `$0.06/GiB/mo`
- Extra outbound transfer (Droplets): `$0.01/GiB`
- Extra outbound transfer (Spaces): `$0.01/GiB`
- Reserved IPv4: free while attached, `$5/mo` when reserved but unattached
- Monitoring: free
- Cloud Firewalls: free
- Uptime checks: `$1/check/mo` (1 free-check credit on invoice)

### Monthly Budget Scenarios

- `[ ]` Lean single-server:
  - `4 GiB droplet ($24) + weekly backups ($4.80) + Spaces ($5) = ~$33.80/mo`
- `[ ]` Balanced (managed DB):
  - `2 GiB droplet ($18) + managed PostgreSQL ($15.15) + weekly backups ($3.60) + Spaces ($5) = ~$41.75/mo`
- `[ ]` Entry high-availability:
  - `2x 2 GiB droplets ($36) + load balancer ($12) + managed PostgreSQL 2 GiB ($30.45) + weekly droplet backups ($7.20) + Spaces ($5) = ~$90.65/mo`

### Credit Constraints (Important)

- `[x]` Confirm your current GitHub Student credit expiration date in DO billing.
- `[x]` Confirm credit applicability to your selected services before provisioning.
- `[!]` Promotional credits do **not** apply to some ineligible charges (for example support plans, Marketplace charges, and upfront prepayments).

## Server Requirements

- `[/]` Provision server or hosting account.
  - DigitalOcean selected; droplet, managed database, and Spaces are provisioned.
  - Remaining provisioning work: OS/app stack setup, firewall hardening, deployment pipeline, and runtime process configuration.
  - Access milestone: SSH access to the production droplet is confirmed.
- `[x]` Install or confirm PHP version compatible with the app.
  - Project target from AGENTS.md: PHP 8.4.1.
  - Composer constraint currently allows PHP `^8.2`.
  - Current server status: PHP 8.4.x installed and confirmed on droplet.
- `[/]` Install required PHP extensions.
  - Minimum expected Laravel extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML.
  - Also verify extensions needed by PhpSpreadsheet exports, such as Zip, XML, GD, and related spreadsheet/image support.
  - Initial Laravel/Spreadsheet-related extension set installed on droplet; final verification pending `composer check-platform-reqs`.
- `[x]` Install Composer on the deployment machine or build machine.
- `[x]` Install Node.js and npm compatible with the Vite build.
- `[ ]` Configure web server to serve only the `public/` directory.
- `[ ]` Configure HTTPS.
- `[ ]` Configure server timezone.
- `[ ]` Configure file upload limits to support announcement attachments up to 10 MB each.
- `[ ]` Configure server memory and execution limits for spreadsheet exports.

## Production Environment

- `[ ]` Create production `.env` from `.env.example`.
- `[ ]` Set `APP_ENV=production`.
- `[ ]` Set `APP_DEBUG=false`.
- `[ ]` Set `APP_URL` to the final HTTPS domain.
- `[ ]` Set `ENROLLMENT_CLAIM_BASE_URL` to the same final HTTPS domain (or explicit claim domain).
- `[ ]` Generate and preserve the production `APP_KEY`.
  - Do not regenerate this key after real users or encrypted data exist.
- `[ ]` Set production database credentials.
- `[ ]` Set production mail credentials.
- `[ ]` Set production SMS/Firebase credentials for claim OTP.
- `[ ]` Set `LOG_LEVEL` appropriately, usually `warning` or `error`.
- `[ ]` Confirm `SESSION_DRIVER`.
  - Current default: `database`.
- `[ ]` Confirm `CACHE_STORE`.
  - Current default: `database`.
- `[ ]` Confirm `QUEUE_CONNECTION`.
  - Current default: `database`.
- `[ ]` Configure `SESSION_SECURE_COOKIE=true` when running over HTTPS.
- `[ ]` Review `SESSION_DOMAIN` if using subdomains.
- `[ ]` Remove or rotate any local/demo credentials.

## Database

- `[ ]` Create production database and database user.
- `[ ]` Grant least-privilege database permissions.
- `[ ]` Run migrations with `php artisan migrate --force`.
- `[ ]` Decide whether production seeders should run.
- `[ ]` Create or verify first super-admin account.
- `[ ]` Verify database-backed sessions table exists.
- `[ ]` Verify database-backed cache table exists.
- `[ ]` Verify database-backed jobs and failed jobs tables exist.
- `[ ]` Confirm migration status with `php artisan migrate:status`.
- `[ ]` Create database backup and restore procedure.
- `[ ]` Test backup restore on a non-production copy before launch.

## Storage And Files

- `[ ]` Ensure `storage/` is writable by the web and queue processes.
- `[ ]` Ensure `bootstrap/cache/` is writable during deployment.
- `[ ]` Run `php artisan storage:link`.
- `[ ]` Confirm public avatars load correctly.
- `[ ]` Confirm announcement attachments upload correctly.
- `[ ]` Confirm private announcement attachments are not publicly exposed.
- `[ ]` Confirm attachment view and download routes work after deployment.
- `[ ]` Confirm temporary spreadsheet export files can be created.
- `[ ]` Confirm system backup files are stored in the expected location.
- `[ ]` Include uploaded files and local backups in the server backup plan.

## Background Processes

- `[ ]` Configure Laravel scheduler cron:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

- `[ ]` Confirm scheduled commands are expected in production:
  - Current scheduler entry: `notifications:dispatch-scheduled` (runs every minute via `schedule:run`).
  - Confirm this dispatcher covers grading, finance, and announcement reminder workflows in production.
  - Manual command paths still available:
    - `grading:send-deadline-reminders`
    - `finance:send-due-reminders`
    - `announcements:send-event-reminders`
- `[ ]` Configure a persistent queue worker with Supervisor, systemd, or the host's process manager.
- `[ ]` Use a production queue command similar to:

```bash
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

- `[ ]` Add `php artisan queue:restart` to the deployment process.
- `[ ]` Confirm failed jobs are recorded and can be reviewed.
- `[ ]` Decide whether database queues are enough or Redis should be used later.

## Build And Deployment

- `[ ]` Decide deployment source branch.
- `[ ]` Decide release directory or direct deploy strategy.
- `[ ]` Install PHP dependencies for production:

```bash
composer install --no-dev --optimize-autoloader
```

- `[ ]` Install Node dependencies:

```bash
npm ci
```

- `[ ]` Build frontend assets:

```bash
npm run build
```

- `[ ]` Run database migrations:

```bash
php artisan migrate --force
```

- `[ ]` Optimize Laravel caches:

```bash
php artisan optimize
```

- `[ ]` Restart queue workers after deploy:

```bash
php artisan queue:restart
```

- `[ ]` Confirm Vite manifest exists after build.
- `[ ]` Confirm generated Wayfinder routes/actions are current if the deployment depends on generated frontend route helpers.
- `[ ]` Document rollback procedure.

## Security Review

- `[ ]` Confirm debug mode is disabled in production.
- `[ ]` Confirm web server cannot serve `.env`, `storage/app/private`, backups, source files, or vendor files directly.
- `[ ]` Confirm HTTPS redirects are configured.
- `[ ]` Confirm cookies are secure and HTTP-only.
- `[ ]` Confirm trusted proxy behavior matches the host/load balancer.
- `[ ]` Review role middleware and dashboard access for each user role.
- `[ ]` Review upload validation and allowed file types.
- `[ ]` Confirm backup files cannot be downloaded without authorization.
- `[ ]` Remove test accounts or rotate their passwords.
- `[ ]` Rotate secrets that were used during development.
- `[ ]` Confirm production logs do not expose sensitive information.

## Mail And Notifications

- `[/]` Configure SMTP or mail provider.
- `[/]` Resend API-based mail configured in app; production DNS verification still required.
- `[ ]` Set `MAIL_FROM_ADDRESS`.
- `[ ]` Set `MAIL_FROM_NAME`.
- `[ ]` Test password reset email.
- `[ ]` Test email verification if enabled.
- `[ ]` Test reminder/notification mail paths if used.
- `[ ]` Confirm mail failures are logged or monitored.

## Account Claim And OTP Readiness

- `[/]` Enrollment-to-claim-email flow implemented.
- `[/]` Queue worker required for claim mail dispatch.
- `[/]` Firebase phone OTP verification integrated in claim flow.
- `[ ]` Add production app host to Firebase Authorized Domains.
- `[ ]` Confirm Phone Auth provider is enabled in Firebase production project.
- `[ ]` Validate OTP deliverability in production region/carrier mix.

## Mobile Number Consistency

- `[x]` Canonical mobile format standardized to `+639XXXXXXXXX`.
- `[x]` Enrollment UI uses fixed `+63` prefix and subscriber input (`9XXXXXXXXX`).
- `[x]` Claim verification UI uses same fixed-prefix input pattern.
- `[ ]` Backfill/migrate legacy stored numbers to canonical format if needed.

## App Functionality Smoke Tests

- `[ ]` Visit `/up` health route.
- `[ ]` Visit the login page.
- `[ ]` Log in as super admin.
- `[ ]` Log in as admin.
- `[ ]` Log in as registrar.
- `[ ]` Log in as finance.
- `[ ]` Log in as teacher.
- `[ ]` Log in as student.
- `[ ]` Log in as parent.
- `[ ]` Confirm each role lands on the correct dashboard.
- `[ ]` Create, edit, cancel, and delete an announcement.
- `[ ]` Upload, view, and download an announcement attachment.
- `[ ]` Upload and view a profile avatar.
- `[ ]` Run a spreadsheet export.
- `[ ]` Create a system backup.
- `[ ]` Confirm backup list updates.
- `[ ]` Confirm finance due reminders do not error.
- `[ ]` Confirm grade deadline reminders do not error.
- `[ ]` Confirm announcement event reminders do not error.

## Verification Commands

- `[!]` Run PHP tests:
  - Current blocker: `php artisan test --compact` fails with duplicate Pest test-case usage in `tests/Feature/Finance/FinanceImportBatchWorkflowTest.php`.
  - Resolve test bootstrap conflict before production cutover.

```bash
php artisan test --compact
```

- `[x]` Run frontend type check:

```bash
npm run types
```

- `[x]` Run frontend build:

```bash
npm run build
```

- `[ ]` Check routes:

```bash
php artisan route:list
```

- `[ ]` Check migration status:

```bash
php artisan migrate:status
```

- `[ ]` Check application details:

```bash
php artisan about
```

- `[ ]` Check platform requirements:

```bash
composer check-platform-reqs --no-interaction
```

## Monitoring And Operations

- `[ ]` Decide log retention.
- `[ ]` Decide error monitoring tool or process.
- `[ ]` Decide uptime monitoring tool.
- `[ ]` Decide backup monitoring process.
- `[ ]` Decide who receives production alerts.
- `[ ]` Document how to restart queue workers.
- `[ ]` Document how to run migrations.
- `[ ]` Document how to enter and exit maintenance mode.
- `[ ]` Document how to restore from backup.
- `[ ]` Document where credentials are stored.

## Launch Readiness

- `[ ]` All required decisions above are resolved.
- `[ ]` Production deployment completed.
- `[ ]` Production migrations completed.
- `[ ]` Queue worker confirmed running.
- `[ ]` Scheduler confirmed running.
- `[ ]` Mail confirmed working.
- `[ ]` File uploads confirmed working.
- `[ ]` Backup procedure confirmed working.
- `[ ]` Critical role flows smoke-tested.
- `[ ]` DNS cutover planned.
- `[ ]` Rollback plan ready.
- `[ ]` Stakeholders approve launch.

## Decision Log

Add dated entries here as decisions are made.

| Date | Decision | Notes |
| --- | --- | --- |
| 2026-04-20 | Hosting readiness checklist created | Initial checklist based on current Laravel/Inertia app structure. |
| 2026-04-20 | Hosting target set to DigitalOcean | Chosen because GitHub Student Developer Pack includes DigitalOcean credits. |
| 2026-04-20 | Production domain set to `msqc.tech` | Domain is currently registered with `get.tech`. |
| 2026-04-20 | Cloudflare recommended for DNS and TLS | DNS cutover to Cloudflare is recommended first; registrar transfer can happen later if desired. |
| 2026-04-20 | DigitalOcean Droplet chosen over App Platform | Current app relies on persistent local files and Laravel-style worker/scheduler control. |
| 2026-04-21 | Added detailed DigitalOcean stack and cost-planning section | Includes droplet size options, optional services, monthly scenarios, and credit constraints for budgeting. |
| 2026-04-22 | Database engine set to Managed PostgreSQL | Chosen for easier operations and cleaner separation from application compute. |
| 2026-04-22 | Spaces selected for production object storage | Bucket(s) provisioned to support public/private file handling and backup offloading. |
| 2026-04-22 | Outbound mail delivery constraint identified | DigitalOcean droplet SMTP ports are blocked by default; third-party email provider/API is required. |
| 2026-04-23 | Deployment approach set to staging-first then production | Use separate staging/prod runtime contexts on the droplet to reduce launch risk. |
| 2026-04-23 | Deployment approach simplified to single environment | `msqc.tech` will be used as demo/testing host for capstone delivery. |
| 2026-04-23 | DNS strategy finalized | Keep domain registration at `get.tech` and move/manage DNS in Cloudflare only. |
| 2026-04-23 | Mail strategy finalized for capstone demo | Use Resend for outbound emails and Cloudflare Email Routing to forward demo account mail to a demo Gmail inbox. |
| 2026-04-23 | Backup storage strategy finalized | Use DigitalOcean Spaces as off-server backup storage target. |
| 2026-04-23 | Account claim flow setup standardized for this device | Local `.env` configured with Resend + Firebase values and claim feature flags; added smoke-test checklist doc. |

## Completion Log

Add dated entries here as tasks are completed.

| Date | Completed Item | Evidence |
| --- | --- | --- |
| 2026-04-22 | Basic Droplet provisioned | DigitalOcean compute instance created in target region. |
| 2026-04-22 | Managed PostgreSQL provisioned | Cluster created and available for app/database wiring. |
| 2026-04-22 | Spaces object storage provisioned | Bucket(s) created for production file storage strategy. |
| 2026-04-22 | SSH access to production droplet confirmed | Successful key-based login from local machine to droplet. |
| 2026-04-22 | Base runtime installed on droplet | Confirmed PHP 8.4.x, Node 22.x, npm, and Composer availability. |
| 2026-04-22 | Local frontend verification passed | `npm run types` and `npm run build` completed successfully. |
| 2026-04-22 | Local backend test verification blocked | `php artisan test --compact` failed due duplicate Pest test-case declaration in finance feature tests. |
| 2026-04-23 | Local claim-flow migrations applied | Pending migrations including account-claim tables were migrated successfully. |
| 2026-04-23 | Account claim routes/tests verified locally | `php artisan route:list --name=account.claim` and `php artisan test --compact tests/Feature/AccountClaimFlowTest.php` both passed. |
