<?php

use App\Http\Controllers\Crm\ActivityController;
use App\Http\Controllers\Crm\AttachmentController;
use App\Http\Controllers\Crm\ClientController;
use App\Http\Controllers\Crm\CompanySettingController;
use App\Http\Controllers\Crm\ContractController;
use App\Http\Controllers\Crm\DashboardController;
use App\Http\Controllers\Crm\InvoiceController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\LeadStageController;
use App\Http\Controllers\Crm\PaymentController;
use App\Http\Controllers\Crm\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('crm', DashboardController::class)->name('crm.dashboard');

    Route::middleware('can:manage-master-data')->group(function () {
        Route::post('lead-stages', [LeadStageController::class, 'store'])->name('lead-stages.store');
        Route::put('lead-stages/{leadStage}', [LeadStageController::class, 'update'])->name('lead-stages.update');
        Route::post('lead-stages/reorder', [LeadStageController::class, 'reorder'])->name('lead-stages.reorder');
        Route::delete('lead-stages/{leadStage}', [LeadStageController::class, 'destroy'])->name('lead-stages.destroy');

        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::post('services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('settings/company', [CompanySettingController::class, 'edit'])->name('company.edit');
        Route::put('settings/company', [CompanySettingController::class, 'update'])->name('company.update');
    });

    Route::get('settings/company/image/{kind}', [CompanySettingController::class, 'image'])
        ->whereIn('kind', ['signature', 'stamp'])
        ->name('company.image');

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/export', [LeadController::class, 'exportCsv'])->middleware('throttle:exports', 'can:manage-records')->name('leads.export');
    Route::delete('leads/bulk', [LeadController::class, 'destroyBulk'])->middleware('can:manage-records')->name('leads.destroy-bulk');
    Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::post('leads/{lead}/move', [LeadController::class, 'move'])->name('leads.move');
    Route::post('leads/{lead}/activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::post('activities/{activity}/toggle', [ActivityController::class, 'toggle'])->name('activities.toggle');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->middleware('can:manage-records')->name('leads.convert');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->middleware('can:manage-records')->name('leads.destroy');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/export', [ClientController::class, 'exportCsv'])->middleware('throttle:exports', 'can:manage-records')->name('clients.export');
    Route::delete('clients/bulk', [ClientController::class, 'destroyBulk'])->middleware('can:manage-records')->name('clients.destroy-bulk');
    Route::get('clients/short-code', [ClientController::class, 'shortCode'])->name('clients.short-code');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])->middleware('can:manage-records')->name('clients.destroy');

    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::get('contracts/export', [ContractController::class, 'exportCsv'])->middleware('throttle:exports', 'can:manage-records')->name('contracts.export');
    Route::delete('contracts/bulk', [ContractController::class, 'destroyBulk'])->middleware('can:manage-records')->name('contracts.destroy-bulk');
    Route::get('contracts/next-number', [ContractController::class, 'nextNumber'])->name('contracts.next-number');
    Route::post('contracts/scope-points', [ContractController::class, 'scopePoints'])->middleware('throttle:ai', 'can:approve-documents')->name('contracts.scope-points');
    Route::post('contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::get('contracts/{contract}/document', [ContractController::class, 'document'])->name('contracts.document');
    Route::put('contracts/{contract}/document', [ContractController::class, 'updateDocument'])->name('contracts.document.update');
    Route::delete('contracts/{contract}/document', [ContractController::class, 'resetDocument'])->name('contracts.document.reset');

    Route::post('contracts/{contract}/clauses', [ContractController::class, 'clauses'])->middleware('throttle:ai', 'can:approve-documents')->name('contracts.clauses');
    Route::post('contracts/{contract}/sign', [ContractController::class, 'sign'])->middleware('can:approve-documents')->name('contracts.sign');
    Route::post('contracts/{contract}/finalize', [ContractController::class, 'finalize'])->middleware('throttle:documents', 'can:approve-documents')->name('contracts.finalize');
    Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->middleware('throttle:documents')->name('contracts.pdf');
    Route::get('contracts/{contract}/signature', [ContractController::class, 'signature'])->name('contracts.signature');
    Route::post('contracts/{contract}/invoice', [ContractController::class, 'invoice'])->middleware('can:approve-documents')->name('contracts.invoice');
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->middleware('can:manage-records')->name('contracts.destroy');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::get('invoices/export', [InvoiceController::class, 'exportCsv'])->middleware('throttle:exports', 'can:manage-records')->name('invoices.export');
    Route::delete('invoices/bulk', [InvoiceController::class, 'destroyBulk'])->middleware('can:manage-records')->name('invoices.destroy-bulk');
    Route::get('invoices/next-number', [InvoiceController::class, 'nextNumber'])->name('invoices.next-number');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->middleware('can:manage-finance')->name('invoices.edit');
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('can:manage-finance')->name('invoices.update');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->middleware('can:manage-finance')->name('invoices.send');
    Route::post('invoices/{invoice}/settle', [InvoiceController::class, 'settle'])->middleware('can:manage-finance')->name('invoices.settle');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->middleware('can:manage-finance')->name('invoices.void');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('throttle:documents')->name('invoices.pdf');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('can:manage-records')->name('invoices.destroy');

    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('can:manage-finance')->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->middleware('can:manage-finance')->name('payments.destroy');

    Route::post('attachments/{type}/{id}', [AttachmentController::class, 'store'])->middleware('throttle:uploads')->name('attachments.store');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
});
