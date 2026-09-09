<?php

use App\Http\Controllers\GeneralController;
use App\Http\Controllers\PendingApprovalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('dashboard', GeneralController::class)
    ->middleware(['auth', 'verified', 'approved'])
    ->name('dashboard');

Route::get('pending', PendingApprovalController::class)
    ->middleware('auth')
    ->name('pending');

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
require __DIR__.'/crm.php';
require __DIR__.'/settings.php';
