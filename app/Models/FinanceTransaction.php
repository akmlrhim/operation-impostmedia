<?php

namespace App\Models;

use App\Enums\FinanceTransactionType;
use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property FinanceTransactionType $type
 * @property string $category
 * @property string $amount
 * @property Carbon $transaction_date
 * @property string|null $notes
 * @property int|null $invoice_id
 * @property int|null $recorded_by
 */
class FinanceTransaction extends Model
{
    use BroadcastsCrmChanges, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FinanceTransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @param  Builder<FinanceTransaction>  $query
     */
    public function scopeIncome(Builder $query): void
    {
        $query->where('type', FinanceTransactionType::Income);
    }

    /**
     * @param  Builder<FinanceTransaction>  $query
     */
    public function scopeExpense(Builder $query): void
    {
        $query->where('type', FinanceTransactionType::Expense);
    }
}
