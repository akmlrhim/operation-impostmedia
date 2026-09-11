<?php

namespace App\Actions\Finance;

use App\Enums\FinanceTransactionType;
use App\Models\FinanceTransaction;
use App\Models\Invoice;

class RecordInvoiceIncome
{
    /**
     * Mencatat pelunasan invoice sebagai pemasukan di modul keuangan.
     * Idempoten: invoice yang sudah punya catatan kas dikembalikan apa adanya.
     */
    public function handle(Invoice $invoice): FinanceTransaction
    {
        $existing = FinanceTransaction::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', FinanceTransactionType::Income)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return FinanceTransaction::create([
            'type' => FinanceTransactionType::Income,
            'category' => 'Pembayaran invoice',
            'amount' => (float) $invoice->amount_paid,
            'transaction_date' => $invoice->paid_at?->toDateString() ?? now()->toDateString(),
            'notes' => $invoice->number.($invoice->client?->company_name ? ' • '.$invoice->client->company_name : ''),
            'recorded_by' => auth()->id(),
            'invoice_id' => $invoice->id,
        ]);
    }
}
