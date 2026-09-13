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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, Invoice $invoice, SyncInvoiceStatus $sync): RedirectResponse
    {
        $this->ensureVisible($invoice);

        $data = $request->validated();
        $file = $data['proof'] ?? null;
        unset($data['proof']);

        $payment = $invoice->payments()->create([
            ...$data,
            'recorded_by' => auth()->id(),
        ]);

        if ($file !== null) {
            $payment->update(['proof_path' => $payment->replaceProof($file)]);
        }

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

    public function proof(Payment $payment): StreamedResponse
    {
        $this->ensureVisible($payment->invoice);

        $disk = Storage::disk(Payment::PROOF_DISK);

        abort_if($payment->proof_path === null || ! $disk->exists($payment->proof_path), 404);

        return $disk->response($payment->proof_path, headers: [
            'Cache-Control' => 'private, max-age=604800',
        ]);
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
