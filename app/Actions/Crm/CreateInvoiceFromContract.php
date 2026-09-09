<?php

namespace App\Actions\Crm;

use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Contract;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use App\Support\CompanyProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateInvoiceFromContract
{
    public function handle(Contract $contract, ?Carbon $periodStart = null): Invoice
    {
        $recurring = $contract->billing_cycle->isRecurring();
        $periodStart ??= $contract->next_invoice_date ?? Carbon::now()->startOfMonth();
        $interval = $contract->billing_cycle->monthInterval();

        $issueDate = Carbon::now();

        return DB::transaction(function () use ($contract, $recurring, $periodStart, $interval, $issueDate): Invoice {
            $invoice = Invoice::create([
                'number' => DocumentSequence::next(
                    DocumentType::Invoice,
                    date: $issueDate,
                    client: $contract->client,
                ),
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'type' => $recurring ? InvoiceType::Recurring : InvoiceType::Invoice,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->copy()->addDays(Invoice::DEFAULT_DUE_DAYS),
                'period_start' => $recurring ? $periodStart->copy() : null,
                'period_end' => $recurring && $interval !== null
                    ? $periodStart->copy()->addMonths($interval)->subDay()
                    : null,
                'discount_amount' => $recurring ? 0 : $contract->discount_amount,
                'tax_percent' => $contract->tax_percent,
                'status' => InvoiceStatus::Draft,
                'billing_snapshot' => $contract->client?->billingSnapshot(),
                'notes' => CompanyProfile::invoiceNotes(),
                'created_by' => auth()->id(),
            ]);

            foreach ($contract->items as $position => $item) {
                $quantity = $recurring ? 1 : (float) $item->quantity;
                $name = $recurring
                    ? $item->name.' - '.$periodStart->translatedFormat('F Y')
                    : $item->name;

                $invoice->items()->create([
                    'service_package_id' => $item->service_package_id,
                    'contract_item_id' => $item->id,
                    'name' => $name,
                    'description' => $item->description,
                    'quantity' => $quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'amount' => $quantity * (float) $item->unit_price,
                    'position' => $position,
                ]);
            }

            $invoice->recalculate();

            if ($recurring && $interval !== null) {
                $contract->update([
                    'next_invoice_date' => $periodStart->copy()->addMonths($interval),
                ]);
            }

            return $invoice;
        });
    }
}
