# System Help Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a real `System Help` page in the shared sidebar footer that opens a role-aware help experience for every authenticated user role.

**Architecture:** Use one authenticated Laravel route and one Inertia page for `System Help`, with role-specific help content supplied from a PHP config file keyed by `UserRole`. Keep the first version static and deterministic so content is easy to edit, test, and review without adding database tables or admin CRUD.

**Tech Stack:** Laravel 12, Inertia v2, React 19, Wayfinder, Tailwind CSS v4, Pest 4

---

### Task 1: Lock The Route And Data Contract With Feature Tests

**Files:**
- Create: `tests/Feature/SystemHelp/SystemHelpPageTest.php`
- Modify: `tests/Feature/RoleAccessTest.php`
- Reference: `app/Enums/UserRole.php`
- Reference: `database/factories/UserFactory.php`

- [ ] **Step 1: Write the failing route and Inertia contract test**

```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected away from the system help page', function () {
    $this->get('/system-help')->assertRedirect('/login');
});

test('system help resolves the shared inertia page for every role', function (UserRole $role) {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get('/system-help')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('system-help/index')
            ->where('role', $role->value)
            ->has('hero.title')
            ->has('hero.summary')
            ->has('sections', fn (Assert $sections) => $sections->etc())
            ->has('quick_links', fn (Assert $links) => $links->etc())
        );
})->with([
    'super admin' => [UserRole::SUPER_ADMIN],
    'admin' => [UserRole::ADMIN],
    'registrar' => [UserRole::REGISTRAR],
    'finance' => [UserRole::FINANCE],
    'teacher' => [UserRole::TEACHER],
    'student' => [UserRole::STUDENT],
    'parent' => [UserRole::PARENT],
]);
```

- [ ] **Step 2: Extend the test to verify role-specific copy really changes**

```php
test('system help returns role specific hero copy', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($teacher)
        ->get('/system-help')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('role', UserRole::TEACHER->value)
            ->where('hero.title', 'Teacher System Help')
        );

    $this->actingAs($student)
        ->get('/system-help')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('role', UserRole::STUDENT->value)
            ->where('hero.title', 'Student System Help')
        );
});
```

- [ ] **Step 3: Add a sidebar regression test for the shared footer entry**

```php
test('authenticated users can load the system help route from the shared shell', function () {
    $user = User::factory()->finance()->create();

    $this->actingAs($user)
        ->get('/system-help')
        ->assertOk();
});
```

- [ ] **Step 4: Run the new tests and confirm they fail**

Run:

```bash
php artisan test --compact tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
```

Expected:

```text
FAIL  Tests\Feature\SystemHelp\SystemHelpPageTest
  x guests are redirected away from the system help page
  x system help resolves the shared inertia page for every role
  x system help returns role specific hero copy
```

- [ ] **Step 5: Commit the failing tests**

```bash
git add tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
git commit -m "test: cover system help route contract"
```

### Task 2: Add The Shared Backend Endpoint And Role-Based Content Source

**Files:**
- Create: `app/Http/Controllers/SystemHelpController.php`
- Create: `config/system-help.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SystemHelp/SystemHelpPageTest.php`

- [ ] **Step 1: Add the authenticated route in `routes/web.php`**

```php
<?php

use App\Http\Controllers\SystemHelpController;
use Illuminate\Support\Facades\Route;

Route::get('system-help', [SystemHelpController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('system-help');
```

- [ ] **Step 2: Create the controller that maps the signed-in user role to help content**

```php
<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemHelpController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        $catalog = config('system-help.roles');
        $content = $catalog[$role->value] ?? $catalog[UserRole::STUDENT->value];

        return Inertia::render('system-help/index', [
            'role' => $role->value,
            'hero' => $content['hero'],
            'sections' => $content['sections'],
            'quick_links' => $content['quick_links'],
        ]);
    }
}
```

- [ ] **Step 3: Create the role-based content catalog in `config/system-help.php`**

```php
<?php

return [
    'roles' => [
        'super_admin' => [
            'hero' => [
                'title' => 'Super Admin System Help',
                'summary' => 'Manage users, permissions, announcements, audit visibility, and system-wide settings from one reference page.',
            ],
            'quick_links' => [
                ['label' => 'User Manager', 'href' => '/super-admin/user-manager'],
                ['label' => 'Permissions', 'href' => '/super-admin/permissions'],
                ['label' => 'System Settings', 'href' => '/super-admin/system-settings'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Create and maintain user accounts.',
                        'Assign access levels and review permission gaps.',
                        'Audit sensitive actions and configuration changes.',
                    ],
                ],
            ],
        ],
        'admin' => [
            'hero' => [
                'title' => 'Admin System Help',
                'summary' => 'Coordinate academic controls, section planning, grade verification, and school reporting workflows.',
            ],
            'quick_links' => [
                ['label' => 'Academic Controls', 'href' => '/admin/academic-controls'],
                ['label' => 'Grade Verification', 'href' => '/admin/grade-verification'],
                ['label' => 'Class Lists', 'href' => '/admin/class-lists'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Maintain school year setup and curriculum data.',
                        'Review grade submissions and deadlines.',
                        'Prepare class and reporting outputs.',
                    ],
                ],
            ],
        ],
        'registrar' => [
            'hero' => [
                'title' => 'Registrar System Help',
                'summary' => 'Support enrollment, student records, imports, promotions, remedials, and student movement tasks.',
            ],
            'quick_links' => [
                ['label' => 'Student Directory', 'href' => '/registrar/student-directory'],
                ['label' => 'Enrollment', 'href' => '/registrar/enrollment'],
                ['label' => 'Permanent Records', 'href' => '/registrar/permanent-records'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Search, review, and update student records.',
                        'Process enrollment and record transitions.',
                        'Handle imports, promotions, and remedial entries.',
                    ],
                ],
            ],
        ],
        'finance' => [
            'hero' => [
                'title' => 'Finance System Help',
                'summary' => 'Handle ledgers, cashier workflows, transactions, discounts, fees, inventory, and daily financial reporting.',
            ],
            'quick_links' => [
                ['label' => 'Student Ledgers', 'href' => '/finance/student-ledgers'],
                ['label' => 'Cashier Panel', 'href' => '/finance/cashier-panel'],
                ['label' => 'Daily Reports', 'href' => '/finance/daily-reports'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Review balances and payment history.',
                        'Post and correct transactions.',
                        'Maintain pricing, discounts, and inventory references.',
                    ],
                ],
            ],
        ],
        'teacher' => [
            'hero' => [
                'title' => 'Teacher System Help',
                'summary' => 'Use this guide to navigate class schedule, attendance, grading, and advisory workflows.',
            ],
            'quick_links' => [
                ['label' => 'Schedule', 'href' => '/teacher/schedule'],
                ['label' => 'Attendance', 'href' => '/teacher/attendance'],
                ['label' => 'Grading Sheet', 'href' => '/teacher/grading-sheet'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Track class schedules and assigned sections.',
                        'Record attendance accurately and on time.',
                        'Manage grading and advisory submissions.',
                    ],
                ],
            ],
        ],
        'student' => [
            'hero' => [
                'title' => 'Student System Help',
                'summary' => 'Find the basics for checking schedules, viewing grades, and understanding what the student portal is for.',
            ],
            'quick_links' => [
                ['label' => 'Schedule', 'href' => '/student/schedule'],
                ['label' => 'Grades', 'href' => '/student/grades'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Check class schedule updates.',
                        'Review available grades.',
                        'Use the portal as a read-focused self-service space.',
                    ],
                ],
            ],
        ],
        'parent' => [
            'hero' => [
                'title' => 'Parent System Help',
                'summary' => 'Follow your learner\'s schedule, grades, and billing information from one page.',
            ],
            'quick_links' => [
                ['label' => 'Schedule', 'href' => '/parent/schedule'],
                ['label' => 'Grades', 'href' => '/parent/grades'],
                ['label' => 'Billing Information', 'href' => '/parent/billing-information'],
            ],
            'sections' => [
                [
                    'title' => 'Core responsibilities',
                    'items' => [
                        'Monitor schedule visibility for the learner.',
                        'Review grade updates when posted.',
                        'Check balances and billing records.',
                    ],
                ],
            ],
        ],
    ],
];
```

- [ ] **Step 4: Re-run the backend tests and confirm the route contract passes**

Run:

```bash
php artisan test --compact tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
```

Expected:

```text
PASS  Tests\Feature\SystemHelp\SystemHelpPageTest
PASS  Tests\Feature\RoleAccessTest
```

- [ ] **Step 5: Commit the backend slice**

```bash
git add app/Http/Controllers/SystemHelpController.php config/system-help.php routes/web.php tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
git commit -m "feat: add system help backend"
```

### Task 3: Build The Shared Inertia Page And Wire The Sidebar Footer Link

**Files:**
- Create: `resources/js/pages/system-help/index.tsx`
- Create: `resources/js/types/system-help.ts`
- Modify: `resources/js/types/index.ts`
- Modify: `resources/js/components/app-sidebar.tsx`
- Generated: `resources/js/routes/system-help/index.ts`
- Test: `tests/Feature/SystemHelp/SystemHelpPageTest.php`

- [ ] **Step 1: Add explicit frontend prop types for the page contract**

```ts
export type SystemHelpHero = {
    title: string;
    summary: string;
};

export type SystemHelpSection = {
    title: string;
    items: string[];
};

export type SystemHelpQuickLink = {
    label: string;
    href: string;
};

export type SystemHelpPageProps = {
    role: string;
    hero: SystemHelpHero;
    sections: SystemHelpSection[];
    quick_links: SystemHelpQuickLink[];
};
```

- [ ] **Step 2: Re-export the new type file from `resources/js/types/index.ts`**

```ts
export type * from './navigation';
export type * from './system-help';
export type * from './ui';
```

- [ ] **Step 3: Create the shared Inertia page**

```tsx
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, SystemHelpPageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Help',
        href: '/system-help',
    },
];

export default function SystemHelpPage({
    role,
    hero,
    sections,
    quick_links,
}: SystemHelpPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Help" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-medium text-primary">
                        {role.replace('_', ' ')}
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {hero.title}
                    </h1>
                    <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                        {hero.summary}
                    </p>
                </section>

                <section className="grid gap-4 lg:grid-cols-3">
                    {quick_links.map((link) => (
                        <Link
                            key={link.href}
                            href={link.href}
                            className="rounded-lg border px-4 py-3 text-sm font-medium transition hover:bg-accent"
                        >
                            {link.label}
                        </Link>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    {sections.map((section) => (
                        <div key={section.title} className="rounded-lg border p-5">
                            <h2 className="text-base font-semibold">
                                {section.title}
                            </h2>
                            <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                                {section.items.map((item) => (
                                    <li key={item}>{item}</li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}
```

- [ ] **Step 4: Replace the sidebar placeholder `#` link with the real named route helper**

```tsx
import { dashboard, systemHelp } from '@/routes';

const footerNavItems: NavItem[] = [
    {
        title: 'System Help',
        href: systemHelp(),
        icon: HelpCircle,
    },
];
```

- [ ] **Step 5: Build the frontend once so the generated Wayfinder route file exists**

Run:

```bash
npm run build
```

Expected:

```text
vite v7
done built in
```

- [ ] **Step 6: Run the targeted backend test again to confirm the page still resolves**

Run:

```bash
php artisan test --compact tests/Feature/SystemHelp/SystemHelpPageTest.php
```

Expected:

```text
PASS  Tests\Feature\SystemHelp\SystemHelpPageTest
```

- [ ] **Step 7: Commit the frontend slice**

```bash
git add resources/js/components/app-sidebar.tsx resources/js/pages/system-help/index.tsx resources/js/routes/system-help/index.ts resources/js/types/index.ts resources/js/types/system-help.ts
git commit -m "feat: add system help page"
```

### Task 4: Format, Verify, And Leave The Feature In A Shippable State

**Files:**
- Modify: `app/Http/Controllers/SystemHelpController.php`
- Modify: `config/system-help.php`
- Modify: `resources/js/components/app-sidebar.tsx`
- Modify: `resources/js/pages/system-help/index.tsx`
- Modify: `resources/js/types/index.ts`
- Modify: `resources/js/types/system-help.ts`
- Modify: `routes/web.php`
- Modify: `tests/Feature/SystemHelp/SystemHelpPageTest.php`
- Modify: `tests/Feature/RoleAccessTest.php`

- [ ] **Step 1: Run Pint on the touched PHP files**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected:

```text
PASS  Laravel Pint
```

- [ ] **Step 2: Run the smallest relevant automated checks**

Run:

```bash
php artisan test --compact tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
npm run build
```

Expected:

```text
PASS  Tests\Feature\SystemHelp\SystemHelpPageTest
PASS  Tests\Feature\RoleAccessTest
vite v7
done built in
```

- [ ] **Step 3: Review the final diff for only the intended surface area**

Run:

```bash
git diff -- app/Http/Controllers/SystemHelpController.php config/system-help.php resources/js/components/app-sidebar.tsx resources/js/pages/system-help/index.tsx resources/js/types/index.ts resources/js/types/system-help.ts routes/web.php tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
```

Expected:

```text
Only the new system help route, config, page, sidebar link, and tests are present.
```

- [ ] **Step 4: Commit the verification pass**

```bash
git add app/Http/Controllers/SystemHelpController.php config/system-help.php resources/js/components/app-sidebar.tsx resources/js/pages/system-help/index.tsx resources/js/types/index.ts resources/js/types/system-help.ts routes/web.php tests/Feature/SystemHelp/SystemHelpPageTest.php tests/Feature/RoleAccessTest.php
git commit -m "chore: verify system help implementation"
```
