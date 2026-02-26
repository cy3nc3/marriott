<?php

use App\Enums\UserRole;
use App\Models\FinanceDueReminderRule;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('finance can view due reminder settings page', function () {
    $finance = User::factory()->finance()->create();

    $this->actingAs($finance)
        ->get('/finance/due-reminder-settings')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/due-reminder-settings/index')
            ->has('rules', 0)
            ->where('automation.auto_send_enabled', true)
            ->where('automation.send_time', '07:30')
            ->where('automation.max_announcements_per_run', null)
        );
});

test('finance can create update and delete due reminder rules', function () {
    $finance = User::factory()->finance()->create();

    $this->actingAs($finance)
        ->post('/finance/due-reminder-settings', [
            'days_before_due' => 3,
            'is_active' => true,
        ])
        ->assertRedirect();

    $rule = FinanceDueReminderRule::query()->first();

    expect($rule)->not()->toBeNull();
    expect($rule?->days_before_due)->toBe(3);
    expect($rule?->is_active)->toBeTrue();

    $this->actingAs($finance)
        ->patch("/finance/due-reminder-settings/{$rule->id}", [
            'days_before_due' => 1,
            'is_active' => false,
        ])
        ->assertRedirect();

    $rule->refresh();

    expect($rule->days_before_due)->toBe(1);
    expect($rule->is_active)->toBeFalse();

    $this->actingAs($finance)
        ->delete("/finance/due-reminder-settings/{$rule->id}")
        ->assertRedirect();

    expect(FinanceDueReminderRule::query()->count())->toBe(0);
});

test('non finance users cannot access due reminder settings page', function () {
    $student = User::factory()->create([
        'role' => UserRole::STUDENT,
    ]);

    $this->actingAs($student)
        ->get('/finance/due-reminder-settings')
        ->assertForbidden();
});

test('finance can update due reminder automation settings', function () {
    $finance = User::factory()->finance()->create();

    $this->actingAs($finance)
        ->patch('/finance/due-reminder-settings/automation', [
            'auto_send_enabled' => false,
            'send_time' => '09:15',
            'max_announcements_per_run' => 25,
        ])
        ->assertRedirect();

    expect(Setting::get('finance_due_reminder_auto_send_enabled'))->toBe('0');
    expect(Setting::get('finance_due_reminder_send_time'))->toBe('09:15');
    expect(Setting::get('finance_due_reminder_max_announcements_per_run'))->toBe('25');
});
