<?php

namespace App\Models;

use App\Enums\ServiceBillingType;
use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $service_id
 * @property string $name
 * @property string $price
 * @property string $unit
 * @property ServiceBillingType $billing_type
 * @property bool $requires_visit
 */
class ServicePackage extends Model
{
    use BroadcastsCrmChanges;

    protected $guarded = ['id'];

    public function crmBroadcastTopic(): string
    {
        return 'services';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_type' => ServiceBillingType::class,
            'price' => 'decimal:2',
            'position' => 'integer',
            'is_active' => 'boolean',
            'requires_visit' => 'boolean',
        ];
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return HasMany<ServicePackagePoint, $this> */
    public function points(): HasMany
    {
        return $this->hasMany(ServicePackagePoint::class)->orderBy('position');
    }

    /** @return HasMany<ContractItem, $this> */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
