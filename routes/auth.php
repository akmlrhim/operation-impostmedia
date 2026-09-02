<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [GoogleAuthController::class, 'create'])->name('login');

    Route::post('auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware(['throttle:10,1', 'turnstile'])
        ->name('google.redirect');

    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');
});

Route::post('logout', [GoogleAuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
