<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $amount
 * @property Carbon $paid_at
 * @property PaymentMethod $method
 */
class Payment extends Model
{
    use BroadcastsCrmChanges, HasAttachments;

    protected $guarded = ['id'];

    public function crmBroadcastTopic(): string
    {
        return 'invoices';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
