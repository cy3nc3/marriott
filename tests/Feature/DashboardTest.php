<?php

use App\Enums\UserRole;
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
