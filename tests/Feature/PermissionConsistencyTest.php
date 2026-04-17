<?php

use App\Models\Permission;
use App\Models\User;

test('announcements permission defaults to full access for announcement managers', function (): void {
    $announcementManagerRoles = ['super_admin', 'admin', 'registrar', 'finance', 'teacher'];

    foreach ($announcementManagerRoles as $role) {
        $permission = Permission::query()
            ->where('role', $role)
            ->where('feature', 'Announcements')
            ->first();

        expect($permission)->not->toBeNull();
        expect($permission?->access_level)->toBe(2);
    }
});

test('announcements route denies access when access level is no access', function (): void {
    $admin = User::factory()->admin()->create();

    Permission::query()->updateOrCreate(
        ['role' => 'admin', 'feature' => 'Announcements'],
        ['module' => 'System', 'access_level' => 0]
    );

    $this->actingAs($admin)
        ->get('/announcements')
        ->assertForbidden();
});

test('announcements route allows read-only access for index but blocks create', function (): void {
    $admin = User::factory()->admin()->create();

    Permission::query()->updateOrCreate(
        ['role' => 'admin', 'feature' => 'Announcements'],
        ['module' => 'System', 'access_level' => 1]
    );

    $this->actingAs($admin)
        ->get('/announcements')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->post('/announcements', [
            'title' => 'Read-only test',
            'content' => 'This write should be blocked.',
            'target_roles' => ['teacher'],
            'expires_at' => now()->addDay()->toDateString(),
        ])
        ->assertForbidden();
});
