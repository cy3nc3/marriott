<?php

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($this->superAdmin);
});

test('super admin can filter audit logs by search and date range', function () {
    $actor = User::factory()->teacher()->create([
        'name' => 'Teacher Tester',
    ]);

    $olderLog = AuditLog::query()->create([
        'user_id' => $actor->id,
        'action' => 'user.created',
        'model_type' => User::class,
        'model_id' => 11,
        'old_values' => null,
        'new_values' => ['role' => 'teacher'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);
    $olderLog->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->save();

    AuditLog::query()->create([
        'user_id' => $actor->id,
        'action' => 'user.updated',
        'model_type' => User::class,
        'model_id' => 12,
        'old_values' => ['role' => 'student'],
        'new_values' => ['role' => 'teacher'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    $targetLog = AuditLog::query()->create([
        'user_id' => $actor->id,
        'action' => 'user.created',
        'model_type' => User::class,
        'model_id' => 13,
        'old_values' => null,
        'new_values' => ['role' => 'student'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);
    $targetLog->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->save();

    $dateFrom = now()->subDays(3)->toDateString();
    $dateTo = now()->toDateString();

    $this->get("/super-admin/audit-logs?search=user.created&date_from={$dateFrom}&date_to={$dateTo}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/audit-logs/index')
            ->where('filters.search', 'user.created')
            ->where('filters.date_from', $dateFrom)
            ->where('filters.date_to', $dateTo)
            ->has('logs.data', 1)
            ->where('logs.data.0.id', $targetLog->id)
        );
});
