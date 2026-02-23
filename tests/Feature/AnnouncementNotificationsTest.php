<?php

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create();
    $this->poster = User::factory()->superAdmin()->create([
        'name' => 'System Admin',
    ]);
    $this->actingAs($this->teacher);
});

test('dashboard shared notifications include only active role-matched announcements', function () {
    Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'For Everyone',
        'content' => 'General notice for all users.',
        'target_roles' => null,
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'For Teachers',
        'content' => 'Teacher notice content.',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'For Students Only',
        'content' => 'Hidden from teacher.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Expired Teacher Notice',
        'content' => 'Already expired.',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->subDay(),
    ]);

    Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Inactive Teacher Notice',
        'content' => 'Not active.',
        'target_roles' => ['teacher'],
        'is_active' => false,
        'expires_at' => now()->addDay(),
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unread_count', 2)
            ->where('notifications.announcements', function ($announcements): bool {
                if (count($announcements) !== 2) {
                    return false;
                }

                $titles = collect($announcements)->pluck('title');

                return $titles->contains('For Everyone')
                    && $titles->contains('For Teachers')
                    && collect($announcements)->every(function (array $announcement): bool {
                        return ($announcement['is_read'] ?? true) === false;
                    });
            })
        );
});

test('marking announcement as read updates shared unread count', function () {
    $announcement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Read Me',
        'content' => 'Please acknowledge this announcement.',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->post("/notifications/announcements/{$announcement->id}/read")
        ->assertRedirect();

    $this->assertDatabaseHas('announcement_reads', [
        'announcement_id' => $announcement->id,
        'user_id' => $this->teacher->id,
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unread_count', 0)
            ->where('notifications.announcements.0.id', $announcement->id)
            ->where('notifications.announcements.0.is_read', true)
        );
});

test('mark all announcements as read only affects visible announcements', function () {
    $teacherAnnouncement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Teacher Update',
        'content' => 'Visible to teacher.',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $globalAnnouncement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Global Update',
        'content' => 'Visible to all.',
        'target_roles' => null,
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $hiddenAnnouncement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Student Hidden',
        'content' => 'Not visible to teacher.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->post('/notifications/announcements/read-all')
        ->assertRedirect();

    expect(AnnouncementRead::query()
        ->where('user_id', $this->teacher->id)
        ->count())->toBe(2);

    $this->assertDatabaseHas('announcement_reads', [
        'announcement_id' => $teacherAnnouncement->id,
        'user_id' => $this->teacher->id,
    ]);

    $this->assertDatabaseHas('announcement_reads', [
        'announcement_id' => $globalAnnouncement->id,
        'user_id' => $this->teacher->id,
    ]);

    $this->assertDatabaseMissing('announcement_reads', [
        'announcement_id' => $hiddenAnnouncement->id,
        'user_id' => $this->teacher->id,
    ]);
});

test('user cannot mark an announcement as read when it is not targeted to their role', function () {
    $announcement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Student Notice',
        'content' => 'Not visible to teacher.',
        'target_roles' => ['student'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->post("/notifications/announcements/{$announcement->id}/read")
        ->assertForbidden();

    $this->assertDatabaseMissing('announcement_reads', [
        'announcement_id' => $announcement->id,
        'user_id' => $this->teacher->id,
    ]);
});

test('opening announcement detail marks it as read and renders full content', function () {
    $announcement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Quarter Exam Reminder',
        'content' => "Full content is visible on detail page.\nPlease bring your permit.",
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->addDays(3),
    ]);

    $this->get("/notifications/announcements/{$announcement->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/announcements/show')
            ->where('announcement.id', $announcement->id)
            ->where('announcement.title', 'Quarter Exam Reminder')
            ->where('announcement.content', "Full content is visible on detail page.\nPlease bring your permit.")
            ->where('announcement.attachments', [])
        );

    $this->assertDatabaseHas('announcement_reads', [
        'announcement_id' => $announcement->id,
        'user_id' => $this->teacher->id,
    ]);
});

test('user cannot open announcement detail when role is not targeted', function () {
    $announcement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Finance-Only Notice',
        'content' => 'Not for teachers.',
        'target_roles' => ['finance'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $this->get("/notifications/announcements/{$announcement->id}")
        ->assertForbidden();
});

test('announcement attachments can be uploaded and viewed by authorized users', function () {
    Storage::fake('local');

    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post('/super-admin/announcements', [
            'title' => 'Attachment Test',
            'content' => 'Please see attached files.',
            'target_roles' => ['teacher'],
            'attachments' => [
                UploadedFile::fake()->create('reminder.png', 200, 'image/png'),
            ],
        ])
        ->assertRedirect();

    $announcement = Announcement::query()->latest('id')->firstOrFail();
    $attachment = $announcement->attachments()->firstOrFail();

    Storage::disk('local')->assertExists($attachment->stored_path);

    $this->actingAs($this->teacher)
        ->get("/notifications/announcements/{$announcement->id}/attachments/{$attachment->id}")
        ->assertSuccessful()
        ->assertHeader('content-type', $attachment->mime_type);

    $this->actingAs($this->teacher)
        ->get("/notifications/announcements/{$announcement->id}/attachments/{$attachment->id}/download")
        ->assertSuccessful();
});

test('announcement attachments cannot be viewed by non-targeted users', function () {
    Storage::fake('local');

    $announcement = Announcement::query()->create([
        'user_id' => $this->poster->id,
        'title' => 'Teacher Attachment Notice',
        'content' => 'Attachment is teacher-only.',
        'target_roles' => ['teacher'],
        'is_active' => true,
        'expires_at' => now()->addDay(),
    ]);

    $path = UploadedFile::fake()->create('locked.png', 200, 'image/png')
        ->store("announcements/{$announcement->id}", 'local');

    $attachment = $announcement->attachments()->create([
        'original_name' => 'locked.png',
        'stored_path' => $path,
        'mime_type' => 'image/png',
        'file_size' => 1200,
    ]);

    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get("/notifications/announcements/{$announcement->id}/attachments/{$attachment->id}")
        ->assertForbidden();
});
