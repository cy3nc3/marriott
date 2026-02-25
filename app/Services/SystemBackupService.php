<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemBackupService
{
    public function createBackup(string $reason = 'manual', array $context = []): array
    {
        $timestamp = now();
        $safeReason = Str::slug($reason, '_') ?: 'manual';
        $fileName = $timestamp->format('Ymd_His')."_{$safeReason}.json";
        $path = "backups/{$fileName}";

        $payload = [
            'generated_at' => $timestamp->toIso8601String(),
            'reason' => $reason,
            'context' => $context,
            'summary' => [
                'users' => User::count(),
                'announcements' => Announcement::count(),
                'academic_years' => AcademicYear::count(),
                'settings' => Setting::count(),
            ],
            'tables' => $this->snapshotTables(),
            'files' => $this->snapshotFiles(),
        ];

        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT));

        Setting::set('latest_backup_at', $timestamp->format('Y-m-d H:i:s'), 'backup');
        Setting::set('latest_backup_file', $fileName, 'backup');
        Setting::set('latest_backup_reason', $reason, 'backup');

        return [
            'file_name' => $fileName,
            'path' => $path,
            'generated_at' => $timestamp->toIso8601String(),
            'reason' => $reason,
        ];
    }

    /**
     * @return array<int, array{
     *     file_name: string,
     *     created_at: string,
     *     size: string,
     *     size_bytes: int,
     *     reason: string
     * }>
     */
    public function listBackups(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('backups')) {
            return [];
        }

        $files = collect($disk->files('backups'))
            ->filter(fn (string $filePath) => Str::endsWith($filePath, '.json'))
            ->sortDesc()
            ->values();

        return $files->map(function (string $filePath) use ($disk) {
            $fileName = basename($filePath);
            $raw = $disk->get($filePath);
            $decoded = json_decode($raw, true);
            $lastModified = $disk->lastModified($filePath);
            $size = $disk->size($filePath);

            return [
                'file_name' => $fileName,
                'created_at' => $decoded['generated_at'] ?? date('c', $lastModified),
                'size' => $this->formatBytes((int) $size),
                'size_bytes' => (int) $size,
                'reason' => $decoded['reason'] ?? 'manual',
            ];
        })->all();
    }

    /**
     * @return array{success: bool, message: string, file_name?: string}
     */
    public function restoreBackup(string $fileName): array
    {
        $safeFileName = basename($fileName);
        $path = "backups/{$safeFileName}";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return [
                'success' => false,
                'message' => 'Selected backup file does not exist.',
            ];
        }

        $raw = $disk->get($path);
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Backup file format is invalid.',
            ];
        }

        $tables = $decoded['tables'] ?? null;
        $files = $decoded['files'] ?? null;

        if (! is_array($tables) || count($tables) === 0) {
            return $this->restoreLegacySettingsBackup($decoded, $safeFileName);
        }

        try {
            DB::beginTransaction();
            Schema::disableForeignKeyConstraints();

            foreach ($this->managedTables() as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();
            }

            foreach ($tables as $table => $rows) {
                if (! Schema::hasTable($table) || ! is_array($rows) || count($rows) === 0) {
                    continue;
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }

            $this->restoreFiles(is_array($files) ? $files : []);

            Schema::enableForeignKeyConstraints();
            DB::commit();

            Setting::set('latest_restore_at', now()->format('Y-m-d H:i:s'), 'backup');
            Setting::set('latest_restore_file', $safeFileName, 'backup');

            return [
                'success' => true,
                'message' => 'Backup restored successfully.',
                'file_name' => $safeFileName,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Schema::enableForeignKeyConstraints();
            report($e);

            return [
                'success' => false,
                'message' => 'Restore failed. Please check backup integrity.',
            ];
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function snapshotTables(): array
    {
        $tables = [];

        foreach ($this->managedTables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $tables[$table] = $rows;
        }

        return $tables;
    }

    /**
     * @return array<int, string>
     */
    private function managedTables(): array
    {
        return [
            'users',
            'academic_years',
            'grade_levels',
            'subjects',
            'sections',
            'students',
            'parent_student',
            'teacher_subjects',
            'subject_assignments',
            'class_schedules',
            'enrollments',
            'grading_rubrics',
            'graded_activities',
            'student_scores',
            'fees',
            'inventory_items',
            'discounts',
            'student_discounts',
            'billing_schedules',
            'transactions',
            'transaction_items',
            'transaction_due_allocations',
            'ledger_entries',
            'attendances',
            'permanent_records',
            'remedial_records',
            'final_grades',
            'student_departures',
            'announcements',
            'announcement_reads',
            'announcement_attachments',
            'announcement_recipients',
            'announcement_event_responses',
            'announcement_reminder_dispatches',
            'grade_submissions',
            'conduct_ratings',
            'finance_due_reminder_rules',
            'finance_due_reminder_dispatches',
            'settings',
            'audit_logs',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function managedFileDirectories(): array
    {
        return [
            'announcements',
        ];
    }

    /**
     * @return array<int, array{path: string, content_base64: string}>
     */
    private function snapshotFiles(): array
    {
        $disk = Storage::disk('local');
        $files = [];

        foreach ($this->managedFileDirectories() as $directory) {
            if (! $disk->exists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $filePath) {
                $content = $disk->get($filePath);
                $files[] = [
                    'path' => $filePath,
                    'content_base64' => base64_encode($content),
                ];
            }
        }

        return $files;
    }

    /**
     * @param  array<int, array{path?: mixed, content_base64?: mixed}>  $files
     */
    private function restoreFiles(array $files): void
    {
        $disk = Storage::disk('local');

        foreach ($this->managedFileDirectories() as $directory) {
            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }
        }

        foreach ($files as $fileRow) {
            $path = (string) ($fileRow['path'] ?? '');
            $encodedContent = (string) ($fileRow['content_base64'] ?? '');

            if (! $this->isManagedFilePath($path) || $encodedContent === '') {
                continue;
            }

            $decoded = base64_decode($encodedContent, true);
            if ($decoded === false) {
                continue;
            }

            $disk->put($path, $decoded);
        }
    }

    private function isManagedFilePath(string $path): bool
    {
        foreach ($this->managedFileDirectories() as $directory) {
            if ($path === $directory || str_starts_with($path, "{$directory}/")) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{success: bool, message: string, file_name?: string}
     */
    private function restoreLegacySettingsBackup(array $decoded, string $fileName): array
    {
        if (! isset($decoded['settings']) || ! is_array($decoded['settings'])) {
            return [
                'success' => false,
                'message' => 'Backup is missing restorable table data.',
            ];
        }

        foreach ($decoded['settings'] as $key => $value) {
            Setting::set((string) $key, is_scalar($value) || $value === null ? $value : json_encode($value), 'backup');
        }

        Setting::set('latest_restore_at', now()->format('Y-m-d H:i:s'), 'backup');
        Setting::set('latest_restore_file', $fileName, 'backup');

        return [
            'success' => true,
            'message' => 'Legacy settings backup restored.',
            'file_name' => $fileName,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
