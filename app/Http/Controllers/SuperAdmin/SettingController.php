<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\SystemBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(SystemBackupService $backupService): Response
    {
        $settings = Setting::allCached();

        return Inertia::render('super_admin/system-settings/index', [
            'settings' => $settings,
            'backups' => $backupService->listBackups(),
        ]);
    }

    public function store(
        Request $request,
        SystemBackupService $backupService,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        $validated = $request->validate([
            'school_name' => 'nullable|string|max:255',
            'school_id' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'maintenance_mode' => 'nullable|boolean',
            'parent_portal' => 'nullable|boolean',
            'backup_interval' => 'nullable|string|in:week,month,custom',
            'backup_interval_days' => 'nullable|integer|min:1|max:365|required_if:backup_interval,custom',
            'backup_on_quarter' => 'nullable|boolean',
            'backup_on_year_end' => 'nullable|boolean',
            'logo' => 'nullable|image|max:2048',
            'header' => 'nullable|image|max:4096',
            'run_backup' => 'nullable|boolean',
            'restore_file' => 'nullable|string',
        ]);

        if (! empty($validated['restore_file'])) {
            $restore = $backupService->restoreBackup($validated['restore_file']);

            if (! $restore['success']) {
                return back()->with('error', $restore['message']);
            }

            $auditLogService->log('backup.restored', Setting::class, null, [
                'file_name' => $restore['file_name'],
            ]);

            return back()->with('success', 'Backup restored successfully.');
        }

        if (($validated['backup_interval'] ?? null) !== 'custom') {
            $validated['backup_interval_days'] = null;
        }

        $settingKeys = [
            'school_name',
            'school_id',
            'address',
            'maintenance_mode',
            'parent_portal',
            'backup_interval',
            'backup_interval_days',
            'backup_on_quarter',
            'backup_on_year_end',
        ];

        $updatedSettings = [];

        foreach ($settingKeys as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = $validated[$key];

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            if (in_array($key, ['backup_interval_days', 'school_id'], true) && $value !== null) {
                $value = (string) $value;
            }

            $group = in_array($key, ['school_name', 'school_id', 'address'], true)
                ? 'system'
                : 'backup';

            Setting::set($key, $value, $group);
            $updatedSettings[$key] = $value;
        }

        if ($request->hasFile('header')) {
            $oldHeader = Setting::get('header');
            $this->deletePublicAsset($oldHeader);

            $path = $request->file('header')->store('settings', 'public');
            Setting::set('header', '/storage/'.$path, 'appearance');
            $updatedSettings['header'] = '/storage/'.$path;
        }

        if ($request->hasFile('logo')) {
            $oldLogo = Setting::get('logo');
            $this->deletePublicAsset($oldLogo);

            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('logo', '/storage/'.$path, 'appearance');
            $updatedSettings['logo'] = '/storage/'.$path;
        }

        if ($updatedSettings !== []) {
            $auditLogService->log('settings.updated', Setting::class, null, $updatedSettings);
        }

        if ($validated['run_backup'] ?? false) {
            $backup = $backupService->createBackup('manual', [
                'trigger' => 'system_settings',
            ]);

            $auditLogService->log('backup.created', Setting::class, null, [
                'file_name' => $backup['file_name'],
                'reason' => $backup['reason'],
            ]);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    private function deletePublicAsset(?string $publicUrl): void
    {
        if (! $publicUrl || ! Str::startsWith($publicUrl, '/storage/')) {
            return;
        }

        $relativePath = Str::replaceFirst('/storage/', '', $publicUrl);
        Storage::disk('public')->delete($relativePath);
    }
}
