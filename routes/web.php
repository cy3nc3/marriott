<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    $role = auth()->user()->role->value;

    return Inertia::render("{$role}/dashboard");
})->middleware(['auth', 'verified'])->name('dashboard');

Route::group([], __DIR__.'/roles/super_admin.php');
Route::group([], __DIR__.'/roles/admin.php');
Route::group([], __DIR__.'/roles/registrar.php');
Route::group([], __DIR__.'/roles/finance.php');
Route::group([], __DIR__.'/roles/teacher.php');
Route::group([], __DIR__.'/roles/student.php');
Route::group([], __DIR__.'/roles/parent.php');

require __DIR__.'/settings.php';
