<?php

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('allows super admin, admin, registrar, finance, and teacher to access announcements management', function (string $roleState): void {
    $user = User::factory()->{$roleState}()->create();

    $this->actingAs($user)
        ->get('/announcements')
        ->assertSuccessful();
})->with([
    'super_admin' => 'superAdmin',
    'admin' => 'admin',
    'registrar' => 'registrar',
    'finance' => 'finance',
    'teacher' => 'teacher',
]);

it('blocks student and parent from accessing announcements management', function (string $roleState): void {
    $user = User::factory()->{$roleState}()->create();

    $this->actingAs($user)
        ->get('/announcements')
        ->assertForbidden();
})->with([
    'student' => 'student',
    'parent' => 'parent',
]);

it('allows admin to create an announcement', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/announcements', [
            'title' => 'Class Suspension',
            'content' => 'Classes are suspended due to weather.',
            'target_roles' => ['teacher', 'student', 'parent'],
            'expires_at' => now()->addDay()->toDateString(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('announcements', [
        'user_id' => $admin->id,
        'title' => 'Class Suspension',
    ]);
});

it('shows only own announcements for non-super admin roles', function (): void {
    $admin = User::factory()->admin()->create();
    $finance = User::factory()->finance()->create();

    $adminAnnouncement = Announcement::query()->create([
        'user_id' => $admin->id,
        'title' => 'Admin Advisory',
        'content' => 'Admin-only publication.',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    Announcement::query()->create([
        'user_id' => $finance->id,
        'title' => 'Finance Advisory',
        'content' => 'Finance publication.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get('/announcements')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements.data', function ($announcements) use ($adminAnnouncement): bool {
                $announcementCollection = collect($announcements)->values();

                return $announcementCollection->count() === 1
                    && (int) $announcementCollection->first()['id'] === $adminAnnouncement->id;
            })
            ->where('roles', function ($roles): bool {
                return collect($roles)->every(
                    fn (array $role): bool => $role['value'] !== 'super_admin'
                );
            })
        );
});

it('keeps super admin role selectable for super admin users', function (): void {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->get('/announcements')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles', function ($roles): bool {
                return collect($roles)->contains(
                    fn (array $role): bool => $role['value'] === 'super_admin'
                );
            })
        );
});

it('blocks non-super admin from targeting super admin role', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/announcements')
        ->post('/announcements', [
            'title' => 'Invalid Target',
            'content' => 'Targeting super admin is not allowed.',
            'target_roles' => ['super_admin'],
        ])
        ->assertRedirect('/announcements')
        ->assertSessionHasErrors('target_roles.0');
});

it('blocks non-super admin from editing and deleting announcements they did not publish', function (): void {
    $admin = User::factory()->admin()->create();
    $registrar = User::factory()->registrar()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $registrar->id,
        'title' => 'Registrar Notice',
        'content' => 'Original content.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->put("/announcements/{$announcement->id}", [
            'title' => 'Edited Title',
            'content' => 'Edited content.',
            'target_roles' => ['student'],
            'expires_at' => now()->addDays(2)->toDateString(),
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete("/announcements/{$announcement->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('announcements', [
        'id' => $announcement->id,
        'title' => 'Registrar Notice',
    ]);
});

it('blocks student from creating announcements', function (): void {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->post('/announcements', [
            'title' => 'Invalid Notice',
            'content' => 'This should not be allowed.',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('announcements', [
        'title' => 'Invalid Notice',
    ]);
});

it('allows publisher to view announcement read report', function (): void {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create([
        'name' => 'Teacher Recipient',
    ]);
    $student = User::factory()->student()->create([
        'name' => 'Student Recipient',
    ]);

    $announcement = Announcement::query()->create([
        'user_id' => $admin->id,
        'title' => 'Quarter Advisory',
        'content' => 'Please review your quarter deliverables.',
        'target_roles' => ['teacher', 'student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    AnnouncementRead::query()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $teacher->id,
        'read_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get("/announcements/{$announcement->id}/report")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/announcements/report')
            ->where('announcement.id', $announcement->id)
            ->where('analytics.recipient_count', 2)
            ->where('analytics.read_count', 1)
            ->where('analytics.unread_count', 1)
            ->where('recipients.data', function ($recipients): bool {
                return collect($recipients)->contains(
                    fn (array $recipient): bool => $recipient['name'] === 'Teacher Recipient'
                        && $recipient['is_read'] === true
                ) && collect($recipients)->contains(
                    fn (array $recipient): bool => $recipient['name'] === 'Student Recipient'
                        && $recipient['is_read'] === false
                );
            })
        );
});

it('allows admin to view reports for announcements published by other roles', function (): void {
    $admin = User::factory()->admin()->create();
    $registrar = User::factory()->registrar()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $registrar->id,
        'title' => 'Registrar Only',
        'content' => 'Owned by registrar.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get("/announcements/{$announcement->id}/report")
        ->assertSuccessful();
});

it('blocks non-admin non-owner publisher from viewing another publishers announcement report', function (): void {
    $finance = User::factory()->finance()->create();
    $registrar = User::factory()->registrar()->create();

    $announcement = Announcement::query()->create([
        'user_id' => $registrar->id,
        'title' => 'Registrar Only',
        'content' => 'Owned by registrar.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($finance)
        ->get("/announcements/{$announcement->id}/report")
        ->assertForbidden();
});
