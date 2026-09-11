<?php

namespace App\Actions\Crm;

use App\Actions\Finance\RecordInvoiceIncome;
use App\Enums\FinanceTransactionType;
use App\Enums\InvoiceStatus;
use App\Models\FinanceTransaction;
use App\Models\Invoice;

class SyncInvoiceStatus
{
    public function handle(Invoice $invoice): Invoice
    {
        $invoice->recalculate();

        if (in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Void], true)) {
            return $invoice;
        }

        $paid = (float) $invoice->amount_paid;
        $target = (float) $invoice->total;

        $status = match (true) {
            $paid >= $target && $target > 0 => InvoiceStatus::Paid,
            $paid > 0 => InvoiceStatus::PartiallyPaid,
            $invoice->due_date->isPast() => InvoiceStatus::Overdue,
            default => InvoiceStatus::Sent,
        };

        $invoice->update([
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Paid ? ($invoice->paid_at ?? now()) : null,
        ]);

        $this->syncFinance($status, $invoice);

        return $invoice;
    }

    private function syncFinance(InvoiceStatus $status, Invoice $invoice): void
    {
        if ($status === InvoiceStatus::Paid) {
            app(RecordInvoiceIncome::class)->handle($invoice);

            return;
        }

        FinanceTransaction::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', FinanceTransactionType::Income)
            ->forceDelete();
    }
}
