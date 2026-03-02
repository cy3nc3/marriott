<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shares active academic year in common inertia props', function () {
    AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $user = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('active_academic_year.name', '2026-2027')
            ->where('active_academic_year.status', 'ongoing')
        );
});

test('dashboard route resolves the correct role component', function (UserRole $role, string $component) {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->has('kpis')
            ->has('alerts')
            ->has('trends')
            ->has('action_links')
            ->where('trends', function ($trends): bool {
                $trendItems = collect($trends)->values()->all();

                if ($trendItems === []) {
                    return false;
                }

                foreach ($trendItems as $trend) {
                    $display = $trend['display'] ?? null;
                    if (! in_array($display, ['line', 'bar', 'area', 'pie'], true)) {
                        return false;
                    }

                    if (! is_array($trend['chart'] ?? null)) {
                        return false;
                    }
                }

                return true;
            })
        );
})->with([
    'super admin' => [UserRole::SUPER_ADMIN, 'super_admin/dashboard'],
    'admin' => [UserRole::ADMIN, 'admin/dashboard'],
    'registrar' => [UserRole::REGISTRAR, 'registrar/dashboard'],
    'finance' => [UserRole::FINANCE, 'finance/dashboard'],
    'teacher' => [UserRole::TEACHER, 'teacher/dashboard'],
    'student' => [UserRole::STUDENT, 'student/dashboard'],
    'parent' => [UserRole::PARENT, 'parent/dashboard'],
]);
