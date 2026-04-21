# Production Audit Findings

Generated: April 17, 2026  
Scope: inconsistencies, mismatches, errors, mistakes, inefficiencies, and production risks.

## Executive Summary

The audit found one critical reliability blocker, several high-priority security and quality issues, and multiple medium-priority policy/performance risks.

## Decision Log (April 17, 2026)

Resolved:

1. Placeholder admin modules (DepEd Reports, SF9): hide from production navigation/routes until implemented.
2. Handheld access for admin grade verification: allowed.
3. Session cookie policy: enforce secure cookies in production.
4. Lint policy for generated actions/routes: keep linting for correctness, but exclude generated files from stylistic import-order enforcement to avoid churn on generated code.
5. Dependency remediation strategy: apply security-driven upgrades immediately (prefer non-breaking updates first), then re-run audits; escalate only advisories that require major-version changes.

Pending input:

1. Trusted proxy allowlist (exact production ingress IPs/CIDRs) still required from infrastructure owners.

## Remediation Status (Updated April 17, 2026)

Implemented:

1. Finding #1 (critical test bootstrap conflict): fixed by removing duplicate Pest test-case binding from `tests/Feature/Finance/CashierPanelTest.php`.
2. Finding #2 (dependency vulnerabilities): NPM and Composer audits now pass (`0 vulnerabilities` / `No security advisories found`).
3. Finding #3 (destructive/broad lint workflow): lint script split into check/fix variants and narrowed to `resources/js`; generated actions/routes are excluded from import-order stylistic enforcement.
4. Finding #4 (lint baseline failures): error-level lint issues addressed; current lint check passes.
5. Finding #5 (shared Inertia props overhead): notifications/permissions moved to lazy shared props; role permissions are cache-backed; announcement recipient checks avoid per-item existence queries when relation is preloaded.
6. Finding #6 (placeholder modules exposed): DepEd Reports and SF9 Generator routes and sidebar items removed from active admin navigation.
7. Finding #7 (mobile policy consistency): grade verification list remains handheld-accessible while mutation endpoints remain protected.
8. Finding #9 (session secure cookie risk): session secure-cookie default now enforces secure transport in production by default.
9. Finding #10 (setState-in-effect anti-patterns): targeted React state sync patterns were refactored out of the previously flagged files.
10. Finding #11 (TypeScript deprecation risk): deprecated `baseUrl` usage removed from `tsconfig.json`.
11. Finding #12 (large frontend chunks): Vite manual chunking added to split heavy vendor bundles.

Partially Implemented / Operational Follow-up Required:

1. Finding #8 (trusted proxies): wildcard trust removed in code, but production still requires explicit `TRUSTED_PROXIES` IP/CIDR values from infrastructure.

## Prioritized Findings

### 1) Critical - Backend test suite is currently non-runnable

The full backend test command failed before running tests because of conflicting Pest test-case declarations.

Evidence:

- [tests/Feature/Finance/CashierPanelTest.php](tests/Feature/Finance/CashierPanelTest.php#L18)
- [tests/Pest.php](tests/Pest.php#L14)

Impact:

- Regression checks are blocked.
- Production defects can slip through because CI/local test execution is not reliable.

Recommendation:

- Remove duplicate test-case binding in the affected test file(s) and keep one source of truth in Pest setup.

### 2) High - Known dependency vulnerabilities are present

Security audits reported known advisories in installed dependencies.

Evidence:

- [composer.lock](composer.lock#L1844)
- [package.json](package.json#L70)
- [package.json](package.json#L73)
- [package.json](package.json#L74)

Observed:

- Composer audit: 2 advisories affecting league/commonmark (CVE-2026-33347, CVE-2026-30838).
- NPM audit: 7 vulnerabilities (2 moderate, 5 high), including axios, follow-redirects, lodash/lodash-es, picomatch, rollup, and vite advisories.

Impact:

- Known vulnerable behavior remains in the dependency graph.

Recommendation:

- Apply security-driven dependency upgrades immediately, prioritizing non-breaking updates first.
- Re-run Composer/NPM audits in CI after updates.
- If any advisory can only be fixed with a major-version upgrade, escalate as a scheduled follow-up with compatibility validation.

### 3) High - Lint workflow is destructive and too broad

The configured lint script auto-fixes code and can mutate the workspace during read-only checks.

Evidence:

- [package.json](package.json#L11)
- [eslint.config.js](eslint.config.js#L68)
- [.gitignore](.gitignore#L29)
- [.gitignore](.gitignore#L9)
- [.gitignore](.gitignore#L10)
- [.gitignore](.gitignore#L11)

Observed:

- Lint uses --fix by default.
- A prior lint run scanned external worktree/vendor content and produced noisy failures.
- Auto-fixes reordered imports across many files, creating unplanned changes.

Impact:

- CI/dev output is noisy and less trustworthy.
- Unintended code churn can be introduced by routine checks.

Recommendation:

- Split into non-mutating lint check and optional fix command.
- Restrict lint scope to intended source directories.
- Keep generated `resources/js/actions/**` and `resources/js/routes/**` in lint scope for correctness checks, but exclude them from stylistic import-order enforcement.

### 4) High - App source lint baseline is failing

Scoped linting of application source still fails with multiple real issues.

Evidence (samples):

- [resources/js/components/app-sidebar.tsx](resources/js/components/app-sidebar.tsx#L32)
- [resources/js/pages/settings/notifications.tsx](resources/js/pages/settings/notifications.tsx#L3)
- [resources/js/pages/settings/password.tsx](resources/js/pages/settings/password.tsx#L197)
- [resources/js/actions/App/Http/Controllers/Admin/index.ts](resources/js/actions/App/Http/Controllers/Admin/index.ts#L2)
- [resources/js/routes/admin/index.ts](resources/js/routes/admin/index.ts#L5)

Observed:

- Scoped ESLint run reported 76 problems (68 errors, 8 warnings).

Impact:

- Frontend quality gate is red and release confidence is reduced.

Recommendation:

- Prioritize fixing error-level issues first (unused vars, import order, effect-state patterns), then warnings.

### 5) High - Shared Inertia props can create request-time DB overhead

Global shared props include notifications and permissions on most authenticated requests.

Evidence:

- [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php#L61)
- [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php#L67)
- [app/Services/AnnouncementNotificationService.php](app/Services/AnnouncementNotificationService.php#L37)
- [app/Services/AnnouncementNotificationService.php](app/Services/AnnouncementNotificationService.php#L158)
- [app/Services/AnnouncementNotificationService.php](app/Services/AnnouncementNotificationService.php#L161)

Impact:

- Higher DB load and latency under concurrency.
- Elevated chance of N+1 style overhead in notification processing.

Recommendation:

- Cache or defer notification/permission payloads and reduce per-item existence checks.

### 6) Medium - Placeholder admin modules are exposed as active navigation items

DepEd Reports and SF9 Generator are routable and linked but still render placeholders.

Evidence:

- [routes/roles/admin.php](routes/roles/admin.php#L45)
- [routes/roles/admin.php](routes/roles/admin.php#L49)
- [resources/js/components/app-sidebar.tsx](resources/js/components/app-sidebar.tsx#L122)
- [resources/js/components/app-sidebar.tsx](resources/js/components/app-sidebar.tsx#L127)
- [resources/js/pages/admin/deped-reports/index.tsx](resources/js/pages/admin/deped-reports/index.tsx#L19)
- [resources/js/pages/admin/sf9-generator/index.tsx](resources/js/pages/admin/sf9-generator/index.tsx#L19)

Impact:

- Users can reach non-functional pages that appear production-ready.

Recommendation:

- Hide DepEd Reports and SF9 Generator from production navigation and direct routing until implementation is complete.

### 7) Medium - Mobile policy consistency risk on admin grade verification

Grade verification index route is not desktop-only while most admin operational routes are.

Evidence:

- [routes/roles/admin.php](routes/roles/admin.php#L39)
- [routes/roles/admin.php](routes/roles/admin.php#L13)
- [resources/js/components/app-sidebar.tsx](resources/js/components/app-sidebar.tsx#L257)

Impact:

- Policy drift may allow sensitive operations on handheld devices unexpectedly.

Recommendation:

- Keep handheld access permitted for admin grade verification and align middleware/docs accordingly to prevent policy drift.

### 8) Medium - Proxy trust is wildcarded

Proxy trust is set to all sources.

Evidence:

- [bootstrap/app.php](bootstrap/app.php#L21)
- [bootstrap/app.php](bootstrap/app.php#L22)

Impact:

- If deployment perimeter is misconfigured, spoofed forwarded headers can affect trust assumptions.

Recommendation:

- Replace wildcard proxy trust with explicit trusted proxy addresses/CIDRs once infrastructure provides the production ingress list.

### 9) Medium - Session cookie transport security depends on env correctness

Session secure-cookie behavior is env-driven with no hard secure default.

Evidence:

- [config/session.php](config/session.php#L172)
- [.env.example](.env.example#L2)
- [.env.example](.env.example#L4)
- [.env.example](.env.example#L21)

Impact:

- Misconfigured production env can weaken cookie transport guarantees.

Recommendation:

- Enforce secure cookies in production (`SESSION_SECURE_COOKIE=true`) and treat this as a deployment requirement.

### 10) Medium - Multiple React setState-in-effect anti-patterns

Several components/pages trigger state updates directly inside effects, flagged by lint as render-cascade risks.

Evidence:

- [resources/js/components/login-form.tsx](resources/js/components/login-form.tsx#L58)
- [resources/js/components/ui/month-picker.tsx](resources/js/components/ui/month-picker.tsx#L53)
- [resources/js/pages/admin/class-lists/index.tsx](resources/js/pages/admin/class-lists/index.tsx#L86)
- [resources/js/pages/admin/schedule-builder/index.tsx](resources/js/pages/admin/schedule-builder/index.tsx#L141)
- [resources/js/pages/finance/fee-structure/index.tsx](resources/js/pages/finance/fee-structure/index.tsx#L178)
- [resources/js/pages/registrar/remedial-entry/index.tsx](resources/js/pages/registrar/remedial-entry/index.tsx#L187)
- [resources/js/pages/teacher/grading-sheet/index.tsx](resources/js/pages/teacher/grading-sheet/index.tsx#L156)
- [resources/js/pages/teacher/grading-sheet/index.tsx](resources/js/pages/teacher/grading-sheet/index.tsx#L209)

Impact:

- Potential render inefficiency and increased risk of subtle state bugs.

Recommendation:

- Refactor with derived state, lazy initialization, memoization, or event-driven updates where appropriate.

### 11) Medium-Low - TypeScript configuration deprecation risk

baseUrl deprecation warning indicates future breakage risk in TS 7.

Evidence:

- [tsconfig.json](tsconfig.json#L110)

Impact:

- Future TypeScript upgrades may break resolution behavior unexpectedly.

Recommendation:

- Migrate to supported path resolution settings and remove deprecated config reliance.

### 12) Low-Medium - Large production frontend chunks

Build artifacts include large JS bundles for key pages.

Evidence:

- [public/build/assets/analytics-panel-CTKNvKhX.js](public/build/assets/analytics-panel-CTKNvKhX.js)
- [public/build/assets/app-7aAuIZ0C.js](public/build/assets/app-7aAuIZ0C.js)

Impact:

- Slower loads for low-end devices and weaker networks.

Recommendation:

- Improve code splitting and lazy-loading strategy for heavy views/components.

## Verification Snapshot

Commands run during audit:

- php artisan test --compact
- npm.cmd run -s types
- npm.cmd run -s build
- composer audit --no-interaction
- npm.cmd audit --omit=dev --audit-level=moderate
- npx eslint resources/js --max-warnings=0
- php artisan route:list --except-vendor

Observed status:

- Backend tests: blocked by Pest bootstrap conflict.
- Type check: passing.
- Production build: passing.
- Composer audit: failing with advisories.
- NPM audit: failing with advisories.
- Scoped app lint: failing.

## Remaining Open Question

1. Is all production traffic guaranteed to pass through trusted proxy infrastructure, and what are the exact trusted ingress proxy IPs/CIDRs?

## Note

A previous lint command in this repo uses auto-fix and can modify files unexpectedly. Prefer non-mutating checks for audit-only runs.
