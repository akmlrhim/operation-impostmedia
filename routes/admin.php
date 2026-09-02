<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved', 'superuser'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
