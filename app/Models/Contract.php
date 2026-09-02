<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasActivities;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property string $title
 * @property ContractType $type
 * @property ContractStatus $status
 * @property BillingCycle $billing_cycle
 * @property string $subtotal
 * @property string $tax_percent
 * @property string $tax_amount
 * @property string $value
 * @property array<string, mixed>|null $ai_clauses
 * @property bool|null $ai_requires_visit
 * @property string|null $body
 * @property string|null $document_body
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $signed_date
 * @property Carbon|null $next_invoice_date
 */
class Contract extends Model
{
    use BroadcastsCrmChanges, HasActivities, HasAttachments, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'status' => ContractStatus::class,
            'billing_cycle' => BillingCycle::class,
            'ai_clauses' => 'array',
            'ai_requires_visit' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_date' => 'date',
            'next_invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return HasMany<ContractItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('position');
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<Contract>  $query
     */
    public function scopeDueForInvoicing(Builder $query): void
    {
        $query->whereIn('status', [ContractStatus::Signed, ContractStatus::Active])
            ->whereNotNull('next_invoice_date')
            ->whereDate('next_invoice_date', '<=', now());
    }

    /**
     * @param  Builder<Contract>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereIn('status', [ContractStatus::Signed, ContractStatus::Active])
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function aiClausePoints(): array
    {
        $clauses = [];

        foreach ((array) ($this->ai_clauses ?? []) as $id => $points) {
            $clean = array_values(array_filter(
                (array) $points,
                fn ($point): bool => is_string($point) && trim($point) !== '',
            ));

            if ($clean !== []) {
                $clauses[(string) $id] = $clean;
            }
        }

        return $clauses;
    }

    /**
     * Pakai hasil bacaan AI (dari WriteContractClauses, yang membaca deskripsi
     * layanan & catatan ruang lingkup) kalau sudah ada; kalau belum pernah
     * ditulis AI, jatuh ke flag manual per paket sebagai perkiraan awal.
     */
    public function requiresVisitClause(): bool
    {
        if ($this->ai_requires_visit !== null) {
            return $this->ai_requires_visit;
        }

        return $this->items->contains(
            fn (ContractItem $item): bool => (bool) $item->servicePackage?->requires_visit,
        );
    }

    public function recalculate(): void
    {
        $subtotal = (float) $this->items()->sum('amount');
        $tax = $subtotal * ((float) $this->tax_percent / 100);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'value' => $subtotal + $tax,
        ])->save();
    }
}
