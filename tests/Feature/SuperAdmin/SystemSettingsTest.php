<?php

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($this->superAdmin);
});

test('super admin can update system settings and action is logged', function () {
    $this->post('/super-admin/system-settings', [
        'school_name' => 'Marriott National High School',
        'school_id' => 'MNHS-001',
        'address' => '123 School Street',
        'maintenance_mode' => true,
        'parent_portal' => false,
        'backup_interval' => 'custom',
        'backup_interval_days' => 10,
        'backup_on_quarter' => true,
        'backup_on_year_end' => false,
    ])->assertRedirect();

    expect(Setting::get('school_name'))->toBe('Marriott National High School');
    expect(Setting::get('school_id'))->toBe('MNHS-001');
    expect(Setting::get('address'))->toBe('123 School Street');
    expect(Setting::get('maintenance_mode'))->toBe('1');
    expect(Setting::get('parent_portal'))->toBe('0');
    expect(Setting::get('backup_interval'))->toBe('custom');
    expect(Setting::get('backup_interval_days'))->toBe('10');
    expect(Setting::get('backup_on_quarter'))->toBe('1');
    expect(Setting::get('backup_on_year_end'))->toBe('0');

    expect(AuditLog::query()
        ->where('action', 'settings.updated')
        ->where('model_type', Setting::class)
        ->exists())->toBeTrue();
});

test('super admin can run backup from settings and action is logged', function () {
    Storage::fake('local');

    $this->post('/super-admin/system-settings', [
        'run_backup' => true,
    ])->assertRedirect();

    $backupFile = Setting::get('latest_backup_file');

    expect($backupFile)->not->toBeNull();
    Storage::disk('local')->assertExists('backups/'.$backupFile);
    expect(AuditLog::query()
        ->where('action', 'backup.created')
        ->where('model_type', Setting::class)
        ->exists())->toBeTrue();
});

test('super admin can restore backup and data is restored with audit log entry', function () {
    Storage::fake('local');

    Setting::set('school_name', 'Baseline School', 'system');

    $this->post('/super-admin/system-settings', [
        'run_backup' => true,
    ])->assertRedirect();

    $backupFile = Setting::get('latest_backup_file');

    expect($backupFile)->not->toBeNull();

    Setting::set('school_name', 'Changed School', 'system');
    expect(Setting::get('school_name'))->toBe('Changed School');

    $this->post('/super-admin/system-settings', [
        'restore_file' => $backupFile,
    ])->assertRedirect();

    expect(Setting::get('school_name'))->toBe('Baseline School');
    expect(AuditLog::query()
        ->where('action', 'backup.restored')
        ->where('model_type', Setting::class)
        ->exists())->toBeTrue();
});
