<?php

use App\Http\Controllers\SuperAdmin\AnnouncementController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\UserManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('super-admin')->name('super_admin.')->group(function () {

    // User Manager (Available in previous view, restoring just in case)
    Route::get('/user-manager', [UserManagerController::class, 'index'])->name('user_manager');
    Route::post('/user-manager', [UserManagerController::class, 'store'])->name('user_manager.store');
    Route::patch('/user-manager/{user}', [UserManagerController::class, 'update'])->name('user_manager.update');
    Route::post('/user-manager/{user}/reset-password', [UserManagerController::class, 'resetPassword'])->name('user_manager.reset_password');
    Route::post('/user-manager/{user}/toggle-status', [UserManagerController::class, 'toggleStatus'])->name('user_manager.toggle_status');

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit_logs');

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');

    // System Settings
    Route::get('/system-settings', [SettingController::class, 'index'])->name('system_settings');
    Route::post('/system-settings', [SettingController::class, 'store'])->name('system_settings.store');
});
