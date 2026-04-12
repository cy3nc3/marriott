# System Audit Findings

Audit date: 2026-04-12

Scope: static review of routes, controllers, pages, seeders, docs, and automated verification.

Update: items 1-4 have been fixed, PWA support has been removed from the current scope, and the dirty worktree boundary has been reviewed.

## Critical Flow / User Experience Blockers

1. Fixed: dynamic permissions could lock out non-super-admin roles after a normal seed.
    - `DatabaseSeeder` now calls `PermissionSeeder`.
    - Feature tests now seed the permission matrix in the shared Pest setup.
    - The shared `Data Import` permission now includes both registrar and finance access.
    - Verified with `tests/Feature/RoleAccessTest.php`, `tests/Feature/Finance/DataImportTest.php`, and registrar data import coverage.

2. Fixed: TypeScript build check failed on the notification settings page.
    - `resources/js/pages/settings/notifications.tsx` now uses the generated Wayfinder route helper for notification settings updates.
    - Verified with `npm.cmd run types`.

3. Fixed: built production manifest was stale and missing active pages.
    - `npm.cmd run build` regenerated the Vite manifest and generated assets.
    - Verified that the previously missing page entries are present in `public/build/manifest.json`.

4. Fixed: PWA support has been removed from current scope and documentation has been aligned.
    - `HANDOFF_SUMMARY.md` now documents PWA state as removed.
    - `capstone-paper/CAPSTONE_SYSTEM_DOCUMENTATION.md` no longer lists PWA manifest support as application scope.
    - Current repo search found no remaining live PWA scope references beyond this audit record and the handoff removal note.

5. Admin DepEd Reports and SF9 Generator are routed but still placeholders.
    - Evidence: `resources/js/pages/admin/deped-reports/index.tsx:19` renders only `PlaceholderPattern` and label text.
    - Evidence: `resources/js/pages/admin/sf9-generator/index.tsx:19` renders only `PlaceholderPattern` and label text.
    - Why it matters: these are visible admin navigation items and are critical reporting/printing expectations for a school system.

6. Teacher SF2 output is still not implemented.
    - Evidence: `resources/js/pages/teacher/attendance/index.tsx:372` renders a disabled `Print SF2` button.
    - Why it matters: attendance entry exists, but DepEd-style SF2 print/export remains unavailable for the teacher workflow.

## Important But Mostly Non-Flow Cleanup / Refactor Items

1. Fixed: default seeding called `StudentSeeder` twice.
    - `DatabaseSeeder` now calls it once.

2. Fixed: test harness now seeds the dynamic permission matrix before feature tests.
    - Vite manifest drift was handled by rebuilding assets.

3. Fixed: documentation is aligned with the removed PWA scope.
    - `HANDOFF_SUMMARY.md` keeps PWA only as a removed-scope note.
    - `capstone-paper/CAPSTONE_SYSTEM_DOCUMENTATION.md` no longer describes PWA manifest support.
    - Future PWA references should be treated as scope changes, not as existing system behavior.

4. Sidebar includes user-facing placeholder routes.
    - Evidence: `resources/js/components/app-sidebar.tsx` links admin users to `/admin/deped-reports` and `/admin/sf9-generator`.
    - Impact: this is only acceptable if those modules are intentionally visible as placeholders; otherwise users will land on dead-end pages.

5. Reviewed: the repo has existing dirty changes, so broad refactors should stay deferred.
    - Current dirty worktree includes registrar SF1 work, production seeders, templates, tests, composer lock changes, PWA removals, permission seeding fixes, 2FA/security test updates, audit logging fixes, and documentation updates.
    - Impact: not a bug by itself, but any broad refactor should wait until these in-progress changes are committed, shelved, or intentionally grouped.

## Verification Run

Original commands run:

```powershell
npm run types
npm.cmd run types
php artisan test --compact
```

Results:

- `npm run types` could not start because PowerShell blocks `npm.ps1` on this machine.
- `npm.cmd run types` ran and failed on `resources/js/pages/settings/notifications.tsx:50`.
- `php artisan test --compact` ran and failed with 123 failed / 149 passed tests. The dominant failure pattern was 403 access denial from missing dynamic permissions; one additional failure exposed the stale Vite manifest for `teacher/attendance/index.tsx`.

Latest verification:

- `npm.cmd run types` passes.
- `npm.cmd run build` passes.
- `php artisan test --compact tests/Feature/RoleAccessTest.php` passes.
- `php artisan test --compact tests/Feature/Finance/DataImportTest.php` passes.
- `php artisan test --compact tests/Feature/Registrar/RegistrarFeaturesTest.php --filter="data import"` passes.
- `php artisan test --compact tests/Feature/Settings/TwoFactorAuthenticationTest.php` passes.
- `php artisan test --compact tests/Feature/SuperAdmin/SystemSettingsTest.php` passes.
- `php artisan test --compact tests/Feature/SuperAdmin/UserManagerTest.php` passes.
- `php artisan test --compact` passes with 272 tests and 3246 assertions.
