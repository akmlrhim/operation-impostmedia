<?php

namespace App\Concerns;

use App\Models\Contract;
use App\Models\Invoice;

trait SyncsLineItems
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Contract|Invoice $document, array $items): void
    {
        foreach ($items as $position => $item) {
            $document->items()->create([
                ...$item,
                'amount' => (float) $item['quantity'] * (float) $item['unit_price'],
                'position' => $position,
            ]);
        }
    }
}
