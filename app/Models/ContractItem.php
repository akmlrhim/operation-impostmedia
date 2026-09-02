<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $quantity
 * @property string $unit_price
 * @property string $amount
 */
class ContractItem extends Model
{
    use BroadcastsCrmChanges;

    protected $guarded = ['id'];

    public function crmBroadcastTopic(): string
    {
        return 'contracts';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Contract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<ServicePackage, $this>
     */
    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function calculateAmount(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
