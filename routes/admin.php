<?php

use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::middleware('superuser')->group(function () {
        Route::get('access', [RolePermissionController::class, 'index'])->name('access.index');
        Route::put('access', [RolePermissionController::class, 'update'])->name('access.update');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
