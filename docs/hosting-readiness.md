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
- `[x]` Configure web server to serve only the `public/` directory.
  - Nginx site `marriott` is enabled with root `/home/deploy/marriott/public` and Laravel front-controller routing.
- `[x]` Configure HTTPS.
  - Let's Encrypt certificate issued and deployed via Certbot for `msqc.tech` and `www.msqc.tech`, with HTTP -> HTTPS redirect enabled.
- `[x]` Configure server timezone.
- `[x]` Configure file upload limits to support announcement attachments up to 10 MB each.
  - Applied on server: `nginx client_max_body_size 12m`, `php upload_max_filesize=12M`, `post_max_size=24M`.
- `[x]` Configure server memory and execution limits for spreadsheet exports.
  - Applied on server: `php memory_limit=512M`, `max_execution_time=180`, `max_input_time=180`.

## Production Environment

- `[x]` Create production `.env` from `.env.example`.
- `[x]` Set `APP_ENV=production`.
- `[x]` Set `APP_DEBUG=false`.
- `[x]` Set `APP_URL` to the final HTTPS domain.
- `[x]` Set `ENROLLMENT_CLAIM_BASE_URL` to the same final HTTPS domain (or explicit claim domain).
- `[x]` Generate and preserve the production `APP_KEY`.
  - Do not regenerate this key after real users or encrypted data exist.
- `[x]` Set production database credentials.
  - Runtime now points to DigitalOcean Managed PostgreSQL over private endpoint with TLS (`sslmode=require`).
- `[x]` Set production mail credentials.
- `[x]` Set production SMS/Firebase credentials for claim OTP.
- `[x]` Set `LOG_LEVEL` appropriately, usually `warning` or `error`.
- `[x]` Confirm `SESSION_DRIVER`.
  - Current default: `database`.
- `[x]` Confirm `CACHE_STORE`.
  - Current default: `database`.
- `[x]` Confirm `QUEUE_CONNECTION`.
  - Current default: `database`.
- `[x]` Configure `SESSION_SECURE_COOKIE=true` when running over HTTPS.
- `[ ]` Review `SESSION_DOMAIN` if using subdomains.
- `[ ]` Remove or rotate any local/demo credentials.

## Database

- `[x]` Create production database and database user.
- `[ ]` Grant least-privilege database permissions.
- `[x]` Run migrations with `php artisan migrate --force`.
- `[x]` Decide whether production seeders should run.
  - Decision: run default seeder for demo environment (`php artisan db:seed --force`).
- `[x]` Create or verify first super-admin account.
  - Verified seeded account: `superadmin@marriott.edu`.
- `[x]` Verify database-backed sessions table exists.
- `[x]` Verify database-backed cache table exists.
- `[x]` Verify database-backed jobs and failed jobs tables exist.
- `[x]` Confirm migration status with `php artisan migrate:status`.
- `[x]` Create database backup and restore procedure.
  - Implemented with `scripts/ops/backup.sh`, `scripts/ops/restore-db.sh`, and runbook `docs/backup-restore.md`.
- `[ ]` Test backup restore on a non-production copy before launch.

## Storage And Files

- `[x]` Ensure `storage/` is writable by the web and queue processes.
- `[x]` Ensure `bootstrap/cache/` is writable during deployment.
- `[x]` Run `php artisan storage:link`.
- `[ ]` Confirm public avatars load correctly.
- `[ ]` Confirm announcement attachments upload correctly.
- `[x]` Confirm private announcement attachments are not publicly exposed.
  - Direct URL probing of `/storage/app/private/` is blocked (HTTP 404).
- `[ ]` Confirm attachment view and download routes work after deployment.
- `[ ]` Confirm temporary spreadsheet export files can be created.
- `[x]` Confirm system backup files are stored in the expected location.
  - Verified backup artifacts under `/home/deploy/backups/marriott`.
- `[x]` Include uploaded files and local backups in the server backup plan.
  - Backup now captures `storage/app` and PostgreSQL locally, then uploads backup artifacts to DigitalOcean Spaces (`marriott-bucket-private/marriott/backups`).

## Background Processes

- `[x]` Configure Laravel scheduler cron:

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
- `[x]` Configure a persistent queue worker with Supervisor, systemd, or the host's process manager.
- `[x]` Use a production queue command similar to:

```bash
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

- `[x]` Add `php artisan queue:restart` to the deployment process.
  - Included in operational runbook via `systemctl restart marriott-queue.service` and `php artisan queue:restart` during deploys.
- `[ ]` Confirm failed jobs are recorded and can be reviewed.
- `[ ]` Decide whether database queues are enough or Redis should be used later.

## Build And Deployment

- `[ ]` Decide deployment source branch.
- `[ ]` Decide release directory or direct deploy strategy.
- `[ ]` Install PHP dependencies for production:

```bash
composer install --no-dev --optimize-autoloader
```

- `[x]` Install Node dependencies:

```bash
npm ci
```

- `[x]` Build frontend assets:

```bash
npm run build
```

- `[x]` Run database migrations:

```bash
php artisan migrate --force
```

- `[/]` Optimize Laravel caches:

```bash
php artisan optimize
```

- `[x]` Restart queue workers after deploy:

```bash
php artisan queue:restart
```

- `[x]` Confirm Vite manifest exists after build.
- `[x]` Confirm generated Wayfinder routes/actions are current if the deployment depends on generated frontend route helpers.
- `[ ]` Document rollback procedure.

## Security Review

- `[x]` Confirm debug mode is disabled in production.
  - `php artisan about` reports `Environment=production` and `Debug Mode=OFF`.
- `[x]` Confirm web server cannot serve `.env`, `storage/app/private`, backups, source files, or vendor files directly.
  - Verified blocked access (HTTP 404) for `.env`, `/storage/app/private/`, `/backups/`, `/vendor/autoload.php`, `/composer.json`, and `/.git/config`.
- `[x]` Confirm HTTPS redirects are configured.
  - Verified `http://msqc.tech/login` responds with `301 -> https://msqc.tech/login`.
- `[x]` Confirm cookies are secure and HTTP-only.
  - Verified `marriottconnect-session` cookie includes `secure; httponly`.
- `[ ]` Confirm trusted proxy behavior matches the host/load balancer.
- `[ ]` Review role middleware and dashboard access for each user role.
- `[ ]` Review upload validation and allowed file types.
- `[x]` Confirm backup files cannot be downloaded without authorization.
  - Direct URL probing of `/backups/` returns HTTP 404.
- `[ ]` Remove test accounts or rotate their passwords.
- `[ ]` Rotate secrets that were used during development.
- `[ ]` Confirm production logs do not expose sensitive information.

## Mail And Notifications

- `[/]` Configure SMTP or mail provider.
- `[/]` Resend API-based mail configured in app; production DNS verification still required.
- `[x]` Set `MAIL_FROM_ADDRESS`.
- `[x]` Set `MAIL_FROM_NAME`.
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

- `[x]` Visit `/up` health route.
- `[x]` Visit the login page.
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
- `[x]` Create a system backup.
- `[x]` Confirm backup list updates.
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

- `[x]` Check routes:

```bash
php artisan route:list
```

- `[x]` Check migration status:

```bash
php artisan migrate:status
```

- `[x]` Check application details:

```bash
php artisan about
```

- `[x]` Check platform requirements:

```bash
composer check-platform-reqs --no-interaction
```

## Monitoring And Operations

- `[ ]` Decide log retention.
- `[ ]` Decide error monitoring tool or process.
- `[ ]` Decide uptime monitoring tool.
- `[x]` Decide backup monitoring process.
  - `marriott-backup.service` now has `OnFailure=marriott-backup-failure-alert.service` using Resend API email alerts.
- `[x]` Decide who receives production alerts.
  - Backup failure alerts route to `inbox.laurence@gmail.com`.
- `[x]` Document how to restart queue workers.
- `[ ]` Document how to run migrations.
- `[ ]` Document how to enter and exit maintenance mode.
- `[x]` Document how to restore from backup.
  - See `docs/backup-restore.md`.
- `[ ]` Document where credentials are stored.

## Launch Readiness

- `[ ]` All required decisions above are resolved.
- `[x]` Production deployment completed.
- `[x]` Production migrations completed.
- `[x]` Queue worker confirmed running.
- `[x]` Scheduler confirmed running.
- `[x]` Mail confirmed working.
  - Verified via successful Resend API test and backup-failure alert service test send.
- `[ ]` File uploads confirmed working.
- `[x]` Backup procedure confirmed working.
- `[ ]` Critical role flows smoke-tested.
- `[x]` DNS cutover planned.
  - Cloudflare DNS now points `msqc.tech` and `www` to the droplet for this demo environment.
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
| 2026-04-24 | Demo deployment mode confirmed | Droplet will be run as a production-like demo environment where iterative refactors are expected during capstone prep. |
| 2026-04-24 | Production-safe app env defaults applied on droplet | Updated `.env` to `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://msqc.tech`, `ENROLLMENT_CLAIM_BASE_URL=https://msqc.tech`, `LOG_LEVEL=warning`, and `SESSION_SECURE_COOKIE=true`; rebuilt Laravel caches. |
| 2026-04-24 | Managed PostgreSQL credentials configured in app runtime | `.env` is configured for `DB_CONNECTION=pgsql` with managed host/port/database/user and `DB_SSLMODE=require`; final runtime cutover completed after grants and migration run. |
| 2026-04-24 | HTTPS certificate and redirect enabled | Certbot issued and deployed certificates for `msqc.tech` and `www.msqc.tech`; nginx now serves valid HTTPS and redirects HTTP to HTTPS. |
| 2026-04-24 | Mail and Firebase production credentials configured | Set `RESEND_API_KEY`, production mail sender identity, claim mail/sms flags, backend Firebase API key, and Vite Firebase client env values; rebuilt Laravel cache and frontend assets. |
| 2026-04-24 | Background process strategy finalized on systemd | Chose systemd-managed queue worker service plus scheduler timer/service for production-like demo reliability on single droplet. |
| 2026-04-24 | Backup automation strategy finalized | Added systemd-driven nightly backup (`marriott-backup.timer`) with PostgreSQL + `storage/app` artifacts and retention handling via ops scripts. |
| 2026-04-24 | Backup failure alert strategy finalized | Added systemd `OnFailure` hook for backup service and Resend-based email notifier to alert operators immediately on backup failure. |

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
| 2026-04-24 | Server deploy baseline completed on droplet | Ran `composer install`, `npm ci`, `npm run build`, generated `APP_KEY`, migrated DB, cached config/routes/views, set writable permissions, created storage symlink, and enabled nginx site for Laravel public root. |
| 2026-04-24 | Runtime fix applied for current droplet setup | Installed `php8.4-sqlite3` and migrated the current configured sqlite database (`database/database.sqlite`) to unblock immediate demo bring-up. |
| 2026-04-24 | Upload/export runtime limits configured | Added nginx and PHP-FPM limits for 10MB+ attachments and export workloads; reloaded nginx and restarted PHP-FPM. |
| 2026-04-24 | Managed PostgreSQL cutover completed | Granted schema privileges to `marriott_app`, ran full `php artisan migrate --force` on `marriott_prod`, and restored `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` to database-backed operation under managed PostgreSQL. |
| 2026-04-24 | Queue worker deployed as persistent service | Created/enabled `marriott-queue.service` (`php artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=120`) and verified it is active. |
| 2026-04-24 | Scheduler deployed as minute timer | Created/enabled `marriott-scheduler.timer` + `marriott-scheduler.service`; verified timer trigger and successful `notifications:dispatch-scheduled` execution in systemd logs. |
| 2026-04-24 | Backup scripts and timer validated | Installed PostgreSQL 18 client for compatibility, successfully ran `marriott-backup.service`, and confirmed backup artifacts are written to `/home/deploy/backups/marriott`. |
| 2026-04-24 | Production hardening spot-checks validated | Verified debug mode OFF, HTTP->HTTPS redirect, queue restart signaling, and blocked direct access to `.env`, backup path, and private storage path. |
| 2026-04-24 | Default demo seeding completed on managed PostgreSQL | Ran `php artisan db:seed --force` (`ProductionThreeYearSnapshotSeeder` chain) and verified seeded admin accounts including `superadmin@marriott.edu`. |
| 2026-04-24 | Off-server backup upload to Spaces enabled and verified | Configured Spaces credentials/endpoint for backup automation, uploaded backup artifacts to `s3://marriott-bucket-private/marriott/backups/`, and verified remote object listing. |
| 2026-04-24 | Backup failure email alert tested successfully | Triggered `marriott-backup-failure-alert.service`; service completed `status=0/SUCCESS` and logged successful send to `inbox.laurence@gmail.com`. |
