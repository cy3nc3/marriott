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
            ->where('kpis', function ($kpis): bool {
                $byId = collect($kpis)->keyBy('id');

                return ($byId['super-recovery-readiness']['meta'] ?? null) === 'Backup timestamp missing'
                    && isset($byId['super-high-risk-ratio'])
                    && isset($byId['super-high-risk-events-today']);
            })
            ->where('alerts.0.id', 'backup-stale')
            ->where('alerts.0.severity', 'critical')
            ->where('trends.0.id', 'admin-actions-last-7-days')
            ->where('trends.0.chart.rows', function ($rows): bool {
                return count($rows) === 7
                    && (int) collect($rows)->sum('actions') === 0;
            })
            ->where('trends.1.id', 'audit-risk-pattern')
            ->where('trends.1.chart.rows', function ($rows): bool {
                $labels = collect($rows)->pluck('type')->all();

                return count($rows) === 2
                    && in_array('Important Actions', $labels, true)
                    && in_array('Other', $labels, true)
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
            ->where('kpis', function ($kpis): bool {
                $byId = collect($kpis)->keyBy('id');

                return ($byId['super-high-risk-ratio']['value'] ?? null) === 12
                    && isset($byId['super-high-risk-events-today'])
                    && isset($byId['super-recovery-readiness']);
            })
            ->where('alerts.0.id', 'audit-risk')
            ->where('alerts.0.severity', 'critical')
            ->where('trends.1.id', 'audit-risk-pattern')
            ->where('trends.1.chart.rows', function ($rows): bool {
                $byType = collect($rows)->keyBy('type');

                return (int) ($byType['Important Actions']['events'] ?? -1) === 12
                    && (int) ($byType['Other']['events'] ?? -1) === 0;
            })
            ->where('trends.0.id', 'admin-actions-last-7-days')
            ->where('trends.0.chart.rows', function ($rows): bool {
                return count($rows) === 7
                    && (int) collect($rows)->sum('actions') >= 12;
            })
        );
});
