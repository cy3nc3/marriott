<?php

use App\Models\Announcement;
use App\Models\AnnouncementRead;
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
        'target_roles' => [],
        'expires_at' => now()->addDays(7)->toDateString(),
    ])->assertRedirect();

    $announcement->refresh();

    expect($announcement->title)->toBe('Updated Maintenance Notice');
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

test('super admin announcement role filter includes global and role-targeted items', function () {
    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'General Memo',
        'content' => 'Applies to all roles.',
        'target_roles' => null,
        'expires_at' => now()->addDays(2),
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Teacher Memo A',
        'content' => 'For teachers only.',
        'target_roles' => ['teacher'],
        'expires_at' => now()->addDays(3),
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Student Memo',
        'content' => 'For students only.',
        'target_roles' => ['student'],
        'expires_at' => now()->addDays(4),
    ]);

    Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Teacher Memo B',
        'content' => 'Second teacher-targeted note.',
        'target_roles' => ['teacher'],
        'expires_at' => now()->addDays(5),
    ]);

    $this->get('/super-admin/announcements?role=teacher')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/announcements/index')
            ->where('filters.role', 'teacher')
            ->where('announcements.data', function ($announcements): bool {
                $announcementCollection = collect($announcements)->values();
                $titles = $announcementCollection->pluck('title');

                return $announcementCollection->count() === 3
                    && $titles->contains('General Memo')
                    && $titles->contains('Teacher Memo A')
                    && $titles->contains('Teacher Memo B')
                    && ! $titles->contains('Student Memo');
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
        'target_roles' => null,
    ]);

    $matchingAnnouncement = Announcement::query()->create([
        'user_id' => $otherPoster->id,
        'title' => 'Operations Advisory',
        'content' => 'Please review this schedule change.',
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
        'target_roles' => ['teacher', 'teacher', 'student'],
    ])->assertRedirect();

    $announcement = Announcement::query()->latest('id')->first();

    expect($announcement)->not->toBeNull();
    expect($announcement->target_roles)->toBe(['teacher', 'student']);
});

test('announcement index includes analytics summary and report links', function () {
    $teacher = User::factory()->teacher()->create([
        'name' => 'Read Recipient',
    ]);
    $student = User::factory()->student()->create([
        'name' => 'Unread Recipient',
    ]);

    $announcement = Announcement::query()->create([
        'user_id' => $this->superAdmin->id,
        'title' => 'Analytics Notice',
        'content' => 'Tracking read metrics.',
        'target_roles' => ['teacher', 'student'],
        'is_active' => true,
        'expires_at' => now()->addDays(2),
    ]);

    AnnouncementRead::query()->create([
        'announcement_id' => $announcement->id,
        'user_id' => $teacher->id,
        'read_at' => now(),
    ]);

    $this->get('/announcements')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/announcements/index')
            ->where('announcements.data.0.id', $announcement->id)
            ->where('announcements.data.0.analytics.recipient_count', 2)
            ->where('announcements.data.0.analytics.read_count', 1)
            ->where('announcements.data.0.analytics.unread_count', 1)
            ->where('announcements.data.0.report_url', route('announcements.report', [
                'announcement' => $announcement->id,
            ]))
            ->where('summary.recipients', 2)
            ->where('summary.unread', 1)
        );
});
