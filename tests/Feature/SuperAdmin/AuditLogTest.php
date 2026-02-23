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

test('super admin audit log listing exposes detailed change payload fields', function () {
    $actor = User::factory()->teacher()->create([
        'name' => 'Teacher Detail Actor',
    ]);

    $detailedLog = AuditLog::query()->create([
        'user_id' => $actor->id,
        'action' => 'settings.updated',
        'model_type' => \App\Models\Setting::class,
        'model_id' => 5,
        'old_values' => [
            'theme' => 'gray',
            'maintenance_mode' => false,
        ],
        'new_values' => [
            'theme' => 'blue',
            'maintenance_mode' => true,
        ],
        'ip_address' => '10.0.0.15',
        'user_agent' => 'Pest Test Agent',
    ]);

    $this->get('/super-admin/audit-logs?search=settings.updated')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/audit-logs/index')
            ->where('filters.search', 'settings.updated')
            ->has('logs.data', 1)
            ->where('logs.data.0.id', $detailedLog->id)
            ->where('logs.data.0.user.name', 'Teacher Detail Actor')
            ->where('logs.data.0.old_values.theme', 'gray')
            ->where('logs.data.0.new_values.theme', 'blue')
            ->where('logs.data.0.old_values.maintenance_mode', false)
            ->where('logs.data.0.new_values.maintenance_mode', true)
            ->where('logs.data.0.ip_address', '10.0.0.15')
            ->where('logs.data.0.user_agent', 'Pest Test Agent')
        );
});

test('super admin can filter audit logs by user search with date_from only', function () {
    $actor = User::factory()->teacher()->create([
        'name' => 'Registrar Auditor',
    ]);

    $olderLog = AuditLog::query()->create([
        'user_id' => $actor->id,
        'action' => 'enrollment.created',
        'model_type' => \App\Models\Enrollment::class,
        'model_id' => 77,
        'old_values' => null,
        'new_values' => ['status' => 'pending_intake'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);
    $olderLog->forceFill([
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ])->save();

    $recentLog = AuditLog::query()->create([
        'user_id' => $actor->id,
        'action' => 'enrollment.updated',
        'model_type' => \App\Models\Enrollment::class,
        'model_id' => 78,
        'old_values' => ['status' => 'pending_intake'],
        'new_values' => ['status' => 'enrolled'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);
    $recentLog->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->save();

    $dateFrom = now()->subDays(2)->toDateString();

    $this->get("/super-admin/audit-logs?search=Registrar Auditor&date_from={$dateFrom}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/audit-logs/index')
            ->where('filters.search', 'Registrar Auditor')
            ->where('filters.date_from', $dateFrom)
            ->has('logs.data', 1)
            ->where('logs.data.0.id', $recentLog->id)
            ->where('logs.data.0.action', 'enrollment.updated')
        );
});
