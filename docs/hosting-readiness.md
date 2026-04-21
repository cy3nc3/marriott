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
- `[/]` Domain registrar / DNS strategy: currently registered with `get.tech`
  - Decision pending: keep registration at `get.tech` and switch only DNS to Cloudflare, or transfer full registration to Cloudflare later.
  - Current recommendation: move DNS to Cloudflare first; full registrar transfer is optional and can wait until the app is stable in production.
- `[x]` SSL/TLS provider: Cloudflare
  - Plan: use Cloudflare for DNS, proxying, and TLS in front of DigitalOcean.
- `[?]` Database engine: undecided
  - Current recommendation: MySQL or PostgreSQL for production.
  - Avoid SQLite for production unless the deployment is intentionally tiny and low-traffic.
- `[?]` Mail provider: undecided
  - Needed for password reset, account verification, reminders, and production notifications.
- `[?]` Backup storage strategy: undecided
  - Decide whether backups stay on the server, are copied off-server, or are stored in cloud/object storage.
- `[?]` Deployment method: undecided
  - Options: manual SSH deploy, GitHub Actions, Laravel Forge deploy script, host panel deployment, or another CI/CD pipeline.
- `[x]` DigitalOcean product choice: Droplet
  - Reason: this Laravel app needs queue workers, scheduled commands, writable local storage, spreadsheet temp files, local backups, and attachment/avatar file handling that fit a VM better than App Platform.
  - Rejected option: App Platform is convenient, but its filesystem is ephemeral and not suitable for the app's current local-storage patterns without additional refactoring to Spaces/object storage and managed background process configuration.

## DigitalOcean Stack And Cost Planning

### Planned Core Architecture

- `[ ]` App compute: `1x Basic Droplet` (Ubuntu LTS)
- `[ ]` Private networking: `1x VPC` (free)
- `[ ]` Network security: `Cloud Firewall` (free)
- `[ ]` SSL/TLS + DNS: `Cloudflare` (free plan)
- `[ ]` Backups: enable Droplet backups (weekly 20% or daily 30% of Droplet price)
- `[ ]` Monitoring: DigitalOcean Monitoring + alerts (free)
- `[ ]` Billing safety: configure billing alert threshold

### Optional Services (Choose Based On Requirements)

- `[?]` Managed PostgreSQL (recommended if you want DB isolation and easier backups)
- `[?]` Spaces object storage (recommended if uploads/backups move off-server)
- `[?]` Regional Load Balancer (only needed when running multiple app droplets)
- `[?]` Uptime checks (optional external health checks)

### Droplet Size Candidates

- `[ ]` 2 GiB / 2 vCPU Basic: `$18/mo`
- `[ ]` 4 GiB / 2 vCPU Basic: `$24/mo` (recommended default starting size)
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

- `[ ]` Confirm your current GitHub Student credit expiration date in DO billing.
- `[ ]` Confirm credit applicability to your selected services before provisioning.
- `[!]` Promotional credits do **not** apply to some ineligible charges (for example support plans, Marketplace charges, and upfront prepayments).

## Server Requirements

- `[/]` Provision server or hosting account.
  - DigitalOcean selected; actual droplet/app/database resources still need to be created.
- `[ ]` Install or confirm PHP version compatible with the app.
  - Project target from AGENTS.md: PHP 8.4.1.
  - Composer constraint currently allows PHP `^8.2`.
- `[ ]` Install required PHP extensions.
  - Minimum expected Laravel extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML.
  - Also verify extensions needed by PhpSpreadsheet exports, such as Zip, XML, GD, and related spreadsheet/image support.
- `[ ]` Install Composer on the deployment machine or build machine.
- `[ ]` Install Node.js and npm compatible with the Vite build.
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
- `[ ]` Generate and preserve the production `APP_KEY`.
  - Do not regenerate this key after real users or encrypted data exist.
- `[ ]` Set production database credentials.
- `[ ]` Set production mail credentials.
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

- `[ ]` Configure SMTP or mail provider.
- `[ ]` Set `MAIL_FROM_ADDRESS`.
- `[ ]` Set `MAIL_FROM_NAME`.
- `[ ]` Test password reset email.
- `[ ]` Test email verification if enabled.
- `[ ]` Test reminder/notification mail paths if used.
- `[ ]` Confirm mail failures are logged or monitored.

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

- `[ ]` Run PHP tests:

```bash
php artisan test --compact
```

- `[ ]` Run frontend type check:

```bash
npm run types
```

- `[ ]` Run frontend build:

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

## Completion Log

Add dated entries here as tasks are completed.

| Date | Completed Item | Evidence |
| --- | --- | --- |
