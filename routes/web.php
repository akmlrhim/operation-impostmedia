<?php

use App\Http\Controllers\GeneralController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PendingApprovalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('dashboard', GeneralController::class)
    ->middleware(['auth', 'verified', 'approved'])
    ->name('dashboard');

Route::get('pending', PendingApprovalController::class)
    ->middleware('auth')
    ->name('pending');

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
require __DIR__.'/crm.php';
require __DIR__.'/finance.php';
require __DIR__.'/settings.php';
