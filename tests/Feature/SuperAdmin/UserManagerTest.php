<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($this->superAdmin);
});

test('super admin user manager index paginates to 15 users per page', function () {
    User::factory()->count(20)->teacher()->create();

    $response = $this->get('/super-admin/user-manager');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/user-manager/index')
            ->where('users.per_page', 15)
            ->where('users.total', 21)
            ->has('users.data', 15)
        );
});

test('super admin user manager actions write audit logs', function () {
    $this->post('/super-admin/user-manager', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birthday' => '2000-01-15',
        'role' => UserRole::TEACHER->value,
    ])->assertRedirect();

    $managedUser = User::query()
        ->where('email', 'john.doe@marriott.edu')
        ->first();

    expect($managedUser)->not->toBeNull();
    expect(AuditLog::query()
        ->where('action', 'user.created')
        ->where('model_type', User::class)
        ->where('model_id', $managedUser->id)
        ->exists())->toBeTrue();

    $this->patch("/super-admin/user-manager/{$managedUser->id}", [
        'first_name' => 'Johnny',
        'last_name' => 'Doe',
        'birthday' => '2000-01-15',
        'role' => UserRole::FINANCE->value,
    ])->assertRedirect();

    expect($managedUser->fresh()->name)->toBe('Johnny Doe');
    expect((string) $managedUser->fresh()->role->value)->toBe(UserRole::FINANCE->value);
    expect(AuditLog::query()
        ->where('action', 'user.updated')
        ->where('model_type', User::class)
        ->where('model_id', $managedUser->id)
        ->exists())->toBeTrue();

    $this->post("/super-admin/user-manager/{$managedUser->id}/reset-password")
        ->assertRedirect();

    expect(Hash::check('20000115', (string) $managedUser->fresh()->password))->toBeTrue();
    expect(AuditLog::query()
        ->where('action', 'user.password_reset')
        ->where('model_type', User::class)
        ->where('model_id', $managedUser->id)
        ->exists())->toBeTrue();

    $this->post("/super-admin/user-manager/{$managedUser->id}/toggle-status")
        ->assertRedirect();

    expect($managedUser->fresh()->is_active)->toBeFalse();
    expect(AuditLog::query()
        ->where('action', 'user.status_toggled')
        ->where('model_type', User::class)
        ->where('model_id', $managedUser->id)
        ->exists())->toBeTrue();
});
