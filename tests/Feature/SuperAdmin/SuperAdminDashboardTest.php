<?php

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($this->superAdmin);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('super admin dashboard keeps role and audit charts safe when logs are empty', function () {
    User::factory()->admin()->count(2)->create();
    User::factory()->registrar()->count(1)->create();
    User::factory()->finance()->count(1)->create();
    User::factory()->teacher()->count(12)->create();
    User::factory()->student()->count(25)->create();
    User::factory()->parent()->count(8)->create();

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/dashboard')
            ->where('kpis.3.id', 'backup-freshness')
            ->where('kpis.3.value', 'Unknown')
            ->where('alerts.0.id', 'backup-stale')
            ->where('alerts.0.severity', 'critical')
            ->where('trends.0.id', 'role-distribution')
            ->where('trends.0.chart.rows', function ($rows): bool {
                $labels = collect($rows)->pluck('role')->all();

                return count($rows) === 7
                    && in_array('Super Admin', $labels, true)
                    && in_array('Admin', $labels, true)
                    && in_array('Registrar', $labels, true)
                    && in_array('Finance', $labels, true)
                    && in_array('Teacher', $labels, true)
                    && in_array('Student', $labels, true)
                    && in_array('Parent', $labels, true)
                    && (int) collect($rows)->sum('users') === User::query()->count();
            })
            ->where('trends.1.id', 'audit-activity')
            ->where('trends.1.chart.rows', function ($rows): bool {
                return count($rows) === 7
                    && (int) collect($rows)->sum('events') === 0;
            })
        );
});

test('super admin dashboard reports high-volume audit risk and chart totals', function () {
    Carbon::setTestNow('2026-02-23 10:00:00');

    Setting::set('latest_backup_at', now()->subHours(2)->toDateTimeString());

    $actor = User::factory()->teacher()->create([
        'name' => 'Audit Actor',
    ]);

    foreach (range(1, 12) as $index) {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'user.deleted',
            'model_type' => User::class,
            'model_id' => $index,
            'old_values' => ['is_active' => true],
            'new_values' => ['is_active' => false],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
        ]);
    }

    foreach (range(13, 15) as $index) {
        $log = AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'user.updated',
            'model_type' => User::class,
            'model_id' => $index,
            'old_values' => ['role' => 'student'],
            'new_values' => ['role' => 'teacher'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
        ]);

        $log->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();
    }

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('super_admin/dashboard')
            ->where('kpis.2.id', 'audit-risk')
            ->where('kpis.2.value', 12)
            ->where('alerts.0.id', 'audit-risk')
            ->where('alerts.0.severity', 'critical')
            ->where('trends.1.id', 'audit-activity')
            ->where('trends.1.chart.rows', function ($rows): bool {
                return count($rows) === 7
                    && (int) collect($rows)->sum('events') === 15;
            })
        );
});
