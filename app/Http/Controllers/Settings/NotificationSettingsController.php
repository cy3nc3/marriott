<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Show the notification settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/notifications', [
            'settings' => $request->user()->notification_settings ?? $this->defaultSettings(),
        ]);
    }

    /**
     * Update the notification settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $request->user()->update([
            'notification_settings' => $validated['settings'],
        ]);

        return back();
    }

    /**
     * Get the default notification settings.
     */
    protected function defaultSettings(): array
    {
        return [
            'email' => [
                'announcements' => true,
                'grade_submissions' => true,
                'system_alerts' => true,
            ],
            'in_app' => [
                'announcements' => true,
                'grade_submissions' => true,
                'system_alerts' => true,
            ],
        ];
    }
}
