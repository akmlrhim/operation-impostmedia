<?php

use App\Http\Controllers\Finance\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/dashboard', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::get('finance/export', [FinanceController::class, 'exportCsv'])->middleware('throttle:exports')->name('finance.export');
    Route::delete('finance/bulk', [FinanceController::class, 'destroyBulk'])->name('finance.destroy-bulk');
    Route::post('finance', [FinanceController::class, 'store'])->name('finance.store');
    Route::put('finance/{financeTransaction}', [FinanceController::class, 'update'])->name('finance.update');
    Route::delete('finance/{financeTransaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');
});
