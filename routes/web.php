<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\ParentPortal\DashboardController as ParentDashboardController;
use App\Http\Controllers\Registrar\DashboardController as RegistrarDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user->role === UserRole::SUPER_ADMIN) {
        return app(SuperAdminDashboardController::class)->index();
    }

    if ($user->role === UserRole::ADMIN) {
        return app(AdminDashboardController::class)->index();
    }

    if ($user->role === UserRole::FINANCE) {
        return app(FinanceDashboardController::class)->index();
    }

    if ($user->role === UserRole::REGISTRAR) {
        return app(RegistrarDashboardController::class)->index();
    }

    if ($user->role === UserRole::TEACHER) {
        return app(TeacherDashboardController::class)->index();
    }

    if ($user->role === UserRole::STUDENT) {
        return app(StudentDashboardController::class)->index();
    }

    if ($user->role === UserRole::PARENT) {
        return app(ParentDashboardController::class)->index();
    }

    return Inertia::render("{$user->role->value}/dashboard");
})->middleware(['auth', 'verified'])->name('dashboard');

Route::group([], __DIR__.'/roles/super_admin.php');
Route::group([], __DIR__.'/roles/admin.php');
Route::group([], __DIR__.'/roles/registrar.php');
Route::group([], __DIR__.'/roles/finance.php');
Route::group([], __DIR__.'/roles/teacher.php');
Route::group([], __DIR__.'/roles/student.php');
Route::group([], __DIR__.'/roles/parent.php');

require __DIR__.'/settings.php';
