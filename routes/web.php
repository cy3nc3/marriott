<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user->role === UserRole::ADMIN) {
        return app(AdminDashboardController::class)->index();
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
