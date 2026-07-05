<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\SystemBackupService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

    public function update(
        UpdateSettingRequest $request,
        AuditLogService $auditLogService,
        SystemBackupService $backupService,
    ): RedirectResponse {
        $validated = $request->validated();

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
            'division',
            'district',
            'principal_name',
            'maintenance_mode',
            'parent_portal',
            'backup_interval',
            'backup_interval_days',
            'backup_on_quarter',
            'backup_on_year_end',
            'teacher_assignment_policy_mode',
            'teacher_assignment_allow_provisional',
            'teacher_assignment_allow_admin_override',
            'teacher_assignment_require_override_reason',
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

            $group = match (true) {
                in_array($key, ['school_name', 'school_id', 'address', 'division', 'district', 'principal_name'], true) => 'system',
                in_array($key, ['backup_interval', 'backup_interval_days', 'backup_on_quarter', 'backup_on_year_end'], true) => 'backup',
                default => 'teacher_qualification',
            };

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

    public function previewBackup(Request $request, SystemBackupService $backupService): \Illuminate\Http\JsonResponse
    {
        $fileName = (string) $request->query('file');
        $preview = $backupService->getBackupPreview($fileName);

        if (! $preview) {
            return response()->json(['error' => 'Backup not found'], 404);
        }

        return response()->json($preview);
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
