<?php

use App\Http\Controllers\SuperAdmin\AnnouncementController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\UserManagerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('super-admin')->name('super_admin.')->group(function () {
    Route::get('/user-manager', [UserManagerController::class, 'index'])->name('user_manager');
    Route::post('/user-manager', [UserManagerController::class, 'store'])->name('user_manager.store');
    Route::patch('/user-manager/{user}', [UserManagerController::class, 'update'])->name('user_manager.update');
    Route::post('/user-manager/{user}/reset-password', [UserManagerController::class, 'resetPassword'])->name('user_manager.reset_password');
    Route::post('/user-manager/{user}/toggle-status', [UserManagerController::class, 'toggleStatus'])->name('user_manager.toggle_status');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit_logs');
    
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');

    Route::get('/system-settings', function () {
        return Inertia::render('super_admin/system-settings/index');
    })->name('system_settings');
});
