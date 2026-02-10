<?php

use App\Enums\UserRole;
use App\Models\User;

test('student cannot access super admin pages', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
    ]);

    $this->actingAs($user)
        ->get('/super-admin/user-manager')
        ->assertStatus(403);
});

test('super admin can access super admin pages', function () {
    $user = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
    ]);

    $this->actingAs($user)
        ->get('/super-admin/user-manager')
        ->assertStatus(200);
});

test('unauthenticated user is redirected to login', function () {
    $this->get('/super-admin/user-manager')
        ->assertRedirect('/login');
});
