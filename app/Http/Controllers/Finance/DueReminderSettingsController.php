<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreDueReminderRuleRequest;
use App\Http\Requests\Finance\UpdateDueReminderAutomationSettingsRequest;
use App\Http\Requests\Finance\UpdateDueReminderRuleRequest;
use App\Models\FinanceDueReminderRule;
use App\Models\Setting;
use App\Services\Scheduling\FinanceDueReminderPlanner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DueReminderSettingsController extends Controller
{
    public function index(): Response
    {
        $rules = FinanceDueReminderRule::query()
            ->withCount('dispatches')
            ->withMax('dispatches', 'sent_at')
            ->orderBy('days_before_due')
            ->get()
            ->map(function (FinanceDueReminderRule $rule): array {
                return [
                    'id' => (int) $rule->id,
                    'days_before_due' => (int) $rule->days_before_due,
                    'label' => $this->formatRuleLabel((int) $rule->days_before_due),
                    'is_active' => (bool) $rule->is_active,
                    'dispatch_count' => (int) $rule->dispatches_count,
                    'last_sent_at' => $rule->dispatches_max_sent_at,
                ];
            })
            ->values();

        return Inertia::render('finance/due-reminder-settings/index', [
            'rules' => $rules,
            'automation' => [
                'auto_send_enabled' => Setting::enabled('finance_due_reminder_auto_send_enabled', true),
                'send_time' => (string) Setting::get('finance_due_reminder_send_time', '07:30'),
                'max_announcements_per_run' => $this->resolveMaxAnnouncementsPerRun(),
            ],
        ]);
    }

    public function store(StoreDueReminderRuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rule = FinanceDueReminderRule::query()->create([
            'days_before_due' => $validated['days_before_due'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        app(FinanceDueReminderPlanner::class)->reconcileRule($rule);

        return back()->with('success', 'Due reminder rule created.');
    }

    public function update(
        UpdateDueReminderRuleRequest $request,
        FinanceDueReminderRule $financeDueReminderRule
    ): RedirectResponse {
        $validated = $request->validated();

        $financeDueReminderRule->update([
            'days_before_due' => $validated['days_before_due'],
            'is_active' => (bool) $validated['is_active'],
            'updated_by' => auth()->id(),
        ]);

        app(FinanceDueReminderPlanner::class)->reconcileRule($financeDueReminderRule);

        return back()->with('success', 'Due reminder rule updated.');
    }

    public function destroy(FinanceDueReminderRule $financeDueReminderRule): RedirectResponse
    {
        app(FinanceDueReminderPlanner::class)->cancelRule($financeDueReminderRule);

        $financeDueReminderRule->delete();

        return back()->with('success', 'Due reminder rule deleted.');
    }

    public function updateAutomation(
        UpdateDueReminderAutomationSettingsRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        Setting::set(
            'finance_due_reminder_auto_send_enabled',
            (bool) $validated['auto_send_enabled'],
            'finance_due_reminders'
        );
        Setting::set(
            'finance_due_reminder_send_time',
            $validated['send_time'],
            'finance_due_reminders'
        );
        Setting::set(
            'finance_due_reminder_max_announcements_per_run',
            $validated['max_announcements_per_run'] ?? null,
            'finance_due_reminders'
        );

        app(FinanceDueReminderPlanner::class)->reconcileAllRules();

        return back()->with('success', 'Reminder automation settings updated.');
    }

    private function formatRuleLabel(int $daysBeforeDue): string
    {
        if ($daysBeforeDue === 0) {
            return 'On due date';
        }

        if ($daysBeforeDue === 1) {
            return '1 day before due';
        }

        return "{$daysBeforeDue} days before due";
    }

    private function resolveMaxAnnouncementsPerRun(): ?int
    {
        $value = Setting::get('finance_due_reminder_max_announcements_per_run');

        if ($value === null || $value === '') {
            return null;
        }

        return max((int) $value, 1);
    }
}
