<?php

namespace App\Http\Controllers\Crm;

use App\Actions\Crm\SyncInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, Invoice $invoice, SyncInvoiceStatus $sync): RedirectResponse
    {
        $invoice->payments()->create([
            ...$request->validated(),
            'recorded_by' => auth()->id(),
        ]);

        $sync->handle($invoice);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pembayaran dicatat.']);

        return to_route('invoices.show', $invoice);
    }

    public function destroy(Payment $payment, SyncInvoiceStatus $sync): RedirectResponse
    {
        $invoice = $payment->invoice;
        $payment->delete();
        $sync->handle($invoice);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pembayaran dihapus.']);

        return back();
    }
}
