<?php

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\User;

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
