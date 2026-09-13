<?php

namespace App\Http\Controllers\Crm;

use App\Actions\Crm\SyncInvoiceStatus;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Crm\Notifier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, Invoice $invoice, SyncInvoiceStatus $sync): RedirectResponse
    {
        $this->ensureVisible($invoice);

        $invoice->payments()->create([
            ...$request->validated(),
            'recorded_by' => auth()->id(),
        ]);

        $sync->handle($invoice);

        Notifier::involved(
            $invoice,
            'invoice',
            $invoice->number,
            $invoice->refresh()->status === InvoiceStatus::Paid
                ? 'Invoice sudah lunas'
                : 'Pembayaran masuk',
            route('invoices.show', $invoice),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pembayaran dicatat.']);

        return to_route('invoices.show', $invoice);
    }

    public function destroy(Payment $payment, SyncInvoiceStatus $sync): RedirectResponse
    {
        $invoice = $payment->invoice;
        $this->ensureVisible($invoice);
        $payment->delete();
        $sync->handle($invoice);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pembayaran dihapus.']);

        return back();
    }
}
