<?php

use App\Http\Controllers\SuperAdmin\AnnouncementController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\SettingController;
use App\Http\Controllers\SuperAdmin\UserManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('super-admin')->name('super_admin.')->group(function () {

    // User Manager (Available in previous view, restoring just in case)
    Route::get('/user-manager', [UserManagerController::class, 'index'])->middleware('desktop_only')->name('user_manager');
    Route::post('/user-manager', [UserManagerController::class, 'store'])->middleware('desktop_only')->name('user_manager.store');
    Route::patch('/user-manager/{user}', [UserManagerController::class, 'update'])->middleware('desktop_only')->name('user_manager.update');
    Route::post('/user-manager/{user}/reset-password', [UserManagerController::class, 'resetPassword'])->middleware('desktop_only')->name('user_manager.reset_password');
    Route::post('/user-manager/{user}/toggle-status', [UserManagerController::class, 'toggleStatus'])->middleware('desktop_only')->name('user_manager.toggle_status');

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit_logs');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/cancel', [AnnouncementController::class, 'cancel'])->name('announcements.cancel');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('desktop_only')->name('permissions');

    // System Settings
    Route::get('/system-settings', [SettingController::class, 'index'])->middleware('desktop_only')->name('system_settings');
    Route::post('/system-settings', [SettingController::class, 'store'])->middleware('desktop_only')->name('system_settings.store');
});
