<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Student;
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
    expect($managedUser?->must_change_password)->toBeTrue();
    expect(Hash::check('john@01152000', (string) $managedUser?->password))->toBeTrue();
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

    expect(Hash::check('johnny@01152000', (string) $managedUser->fresh()->password))->toBeTrue();
    expect($managedUser->fresh()->must_change_password)->toBeTrue();
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

test('last active super admin cannot be demoted', function () {
    $this->patch("/super-admin/user-manager/{$this->superAdmin->id}", [
        'first_name' => 'System',
        'last_name' => 'Owner',
        'birthday' => '2000-01-01',
        'role' => UserRole::ADMIN->value,
    ])->assertRedirect()
        ->assertSessionHas('error');

    expect($this->superAdmin->fresh()->role->value)->toBe(UserRole::SUPER_ADMIN->value);
});

test('last active super admin cannot be deactivated', function () {
    $this->post("/super-admin/user-manager/{$this->superAdmin->id}/toggle-status")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($this->superAdmin->fresh()->is_active)->toBeTrue();
});

test('user manager search is case insensitive', function () {
    $managedUser = User::factory()->create([
        'first_name' => 'Jade',
        'last_name' => 'Godalle',
        'name' => 'Jade Godalle',
        'email' => 'jade.godalle@marriott.edu',
        'role' => UserRole::STUDENT->value,
    ]);

    $this->get('/super-admin/user-manager?search=JADE')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/user-manager/index')
            ->where('filters.search', 'JADE')
            ->where('users.data', function ($rows) use ($managedUser): bool {
                return collect($rows)->contains(function ($row) use ($managedUser): bool {
                    return (int) ($row['id'] ?? 0) === (int) $managedUser->id;
                });
            })
        );
});

test('super admin resets parent password using linked student default pattern', function () {
    $student = Student::query()->create([
        'lrn' => '123123123123',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'birthdate' => '2011-05-12',
    ]);

    $parent = User::factory()->create([
        'first_name' => 'Parent',
        'last_name' => 'Santos',
        'name' => 'Parent Santos',
        'email' => 'parent.123123123123@marriott.edu',
        'birthday' => '1980-01-01',
        'role' => UserRole::PARENT->value,
        'password' => 'temporary-password',
    ]);

    $parent->students()->attach($student);

    $this->post("/super-admin/user-manager/{$parent->id}/reset-password")
        ->assertRedirect();

    expect(Hash::check('maria@05122011', (string) $parent->fresh()->password))->toBeTrue();
    expect($parent->fresh()->must_change_password)->toBeTrue();
});
