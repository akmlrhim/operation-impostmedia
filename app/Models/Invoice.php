<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasActivities;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOwners;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $client_id
 * @property-read Client|null $client
 * @property string $number
 * @property InvoiceType $type
 * @property InvoiceStatus $status
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_percent
 * @property string $tax_amount
 * @property string $total
 * @property string $amount_paid
 * @property string $balance_due
 * @property array<string, mixed>|null $billing_snapshot
 */
class Invoice extends Model
{
    use BroadcastsCrmChanges, HasActivities, HasAttachments, HasOwners, SoftDeletes;

    protected $guarded = ['id'];

    public const DEFAULT_DUE_DAYS = 14;

    /**
     * @return array{subject: string, label: string, url: string}
     */
    public function notificationMeta(): array
    {
        return [
            'subject' => 'Invoice',
            'label' => $this->number,
            'url' => route('invoices.show', $this),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'billing_snapshot' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereIn('invoices.status', InvoiceStatus::outstanding());
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->outstanding()->where('invoices.due_date', '<', now()->toDateString());
    }

    public function recalculate(): void
    {
        $subtotal = (float) $this->items()->sum('amount');
        $base = max($subtotal - (float) $this->discount_amount, 0);
        $tax = $base * ((float) $this->tax_percent / 100);
        $total = $base + $tax;
        $paid = (float) $this->payments()->sum('amount');

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $total,
            'amount_paid' => $paid,
            'balance_due' => $total - $paid,
        ])->save();
    }
}
