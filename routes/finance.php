<?php

use App\Http\Controllers\Finance\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('finance', [FinanceController::class, 'index'])->middleware('can:view-finance')->name('finance.index');
    Route::get('finance/dashboard', [FinanceController::class, 'dashboard'])->middleware('can:view-finance')->name('finance.dashboard');
    Route::get('finance/export', [FinanceController::class, 'exportCsv'])->middleware('throttle:exports', 'can:export-finance')->name('finance.export');
    Route::delete('finance/bulk', [FinanceController::class, 'destroyBulk'])->middleware('can:manage-finance')->name('finance.destroy-bulk');
    Route::post('finance', [FinanceController::class, 'store'])->middleware('can:manage-finance')->name('finance.store');
    Route::put('finance/{financeTransaction}', [FinanceController::class, 'update'])->middleware('can:manage-finance')->name('finance.update');
    Route::delete('finance/{financeTransaction}', [FinanceController::class, 'destroy'])->middleware('can:manage-finance')->name('finance.destroy');
});
