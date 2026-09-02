<?php

namespace App\Actions\Crm;

use App\Concerns\SyncsLineItems;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateInvoice
{
    use SyncsLineItems;

    /**
     * @param  array<string, mixed>  $data  Data invoice tervalidasi, tanpa kunci `items`/`number`.
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $data, array $items, ?string $number): Invoice
    {
        $issueDate = Carbon::parse($data['issue_date']);

        return DB::transaction(function () use ($data, $items, $number, $issueDate): Invoice {
            $client = Client::query()->findOrFail((int) $data['client_id']);

            $invoice = Invoice::create([
                ...$data,
                'number' => $number ?? DocumentSequence::next(
                    DocumentType::Invoice,
                    date: $issueDate,
                    client: $client,
                ),
                'billing_snapshot' => $client->billingSnapshot(),
                'created_by' => auth()->id(),
            ]);

            $this->syncItems($invoice, $items);
            $invoice->recalculate();

            if ($number !== null) {
                DocumentSequence::claim(
                    DocumentType::Invoice,
                    $number,
                    date: $issueDate,
                    client: $client,
                );
            }

            return $invoice;
        });
    }
}
