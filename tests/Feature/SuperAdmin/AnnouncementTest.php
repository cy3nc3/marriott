<?php

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($this->superAdmin);
});

test('super admin announcement crud actions write audit logs', function () {
    $this->post('/super-admin/announcements', [
        'title' => 'Maintenance Notice',
        'content' => 'System will be down this weekend.',
        'priority' => 'high',
        'target_roles' => ['teacher', 'student'],
        'expires_at' => now()->addDays(5)->toDateString(),
    ])->assertRedirect();

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement)->not->toBeNull();
    expect($announcement->title)->toBe('Maintenance Notice');
    expect($announcement->target_roles)->toBe(['teacher', 'student']);
    expect(AuditLog::query()
        ->where('action', 'announcement.created')
        ->where('model_type', Announcement::class)
        ->where('model_id', $announcement->id)
        ->exists())->toBeTrue();

    $this->put("/super-admin/announcements/{$announcement->id}", [
        'title' => 'Updated Maintenance Notice',
        'content' => 'System will be down on Sunday only.',
        'priority' => 'critical',
        'target_roles' => [],
        'expires_at' => now()->addDays(7)->toDateString(),
    ])->assertRedirect();

    $announcement->refresh();

    expect($announcement->title)->toBe('Updated Maintenance Notice');
    expect($announcement->priority)->toBe('critical');
    expect($announcement->target_roles)->toBeNull();
    expect(AuditLog::query()
        ->where('action', 'announcement.updated')
        ->where('model_type', Announcement::class)
        ->where('model_id', $announcement->id)
        ->exists())->toBeTrue();

    $this->delete("/super-admin/announcements/{$announcement->id}")
        ->assertRedirect();

    expect(Announcement::query()->whereKey($announcement->id)->exists())->toBeFalse();
    expect(AuditLog::query()
        ->where('action', 'announcement.deleted')
        ->where('model_type', Announcement::class)
        ->where('model_id', $announcement->id)
        ->exists())->toBeTrue();
});

test('super admin announcement filters include global and role-targeted items', function () {
    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'General High Priority',
        'content' => 'Applies to all roles.',
        'priority' => 'high',
        'target_roles' => null,
        'expires_at' => now()->addDays(2),
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Teacher High Priority',
        'content' => 'For teachers only.',
        'priority' => 'high',
        'target_roles' => ['teacher'],
        'expires_at' => now()->addDays(3),
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Student High Priority',
        'content' => 'For students only.',
        'priority' => 'high',
        'target_roles' => ['student'],
        'expires_at' => now()->addDays(4),
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Teacher Low Priority',
        'content' => 'Low priority note.',
        'priority' => 'low',
        'target_roles' => ['teacher'],
        'expires_at' => now()->addDays(5),
    ]);

    $this->get('/super-admin/announcements?priority=high&role=teacher')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/announcements/index')
            ->where('filters.priority', 'high')
            ->where('filters.role', 'teacher')
            ->has('announcements.data', 2)
            ->where('announcements.data.0.title', function (string $title): bool {
                return in_array($title, ['Teacher High Priority', 'General High Priority'], true);
            })
            ->where('announcements.data.1.title', function (string $title): bool {
                return in_array($title, ['Teacher High Priority', 'General High Priority'], true);
            })
        );
});

test('super admin announcement search can match poster name', function () {
    $otherPoster = User::factory()->superAdmin()->create([
        'name' => 'Alyssa Poster',
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Regular Memo',
        'content' => 'No matching poster name.',
        'priority' => 'normal',
        'target_roles' => null,
    ]);

    $matchingAnnouncement = Announcement::query()->create([
        'user_id' => $otherPoster->id,
        'title' => 'Operations Advisory',
        'content' => 'Please review this schedule change.',
        'priority' => 'normal',
        'target_roles' => ['teacher'],
    ]);

    $this->get('/super-admin/announcements?search=Alyssa')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/announcements/index')
            ->where('filters.search', 'Alyssa')
            ->has('announcements.data', 1)
            ->where('announcements.data.0.id', $matchingAnnouncement->id)
            ->where('announcements.data.0.user.name', 'Alyssa Poster')
        );
});

test('super admin announcement store normalizes duplicate target roles', function () {
    $this->post('/super-admin/announcements', [
        'title' => 'Role Test',
        'content' => 'Role dedupe test.',
        'priority' => 'normal',
        'target_roles' => ['teacher', 'teacher', 'student'],
    ])->assertRedirect();

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement)->not->toBeNull();
    expect($announcement->target_roles)->toBe(['teacher', 'student']);
});
