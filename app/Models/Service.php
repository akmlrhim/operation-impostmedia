<?php

namespace App\Models;

use App\Enums\ServiceType;
use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @property int $id
 * @property ServiceType $type
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 */
class Service extends Model
{
    use BroadcastsCrmChanges;

    protected $guarded = ['id'];

    /**
     * @var list<string>
     */
    protected $appends = ['type_label'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ServiceType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->type->label());
    }

    /** @return HasMany<ServicePackage, $this> */
    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class)->orderBy('position');
    }

    /**
     * @return HasManyThrough<ContractItem, ServicePackage, $this>
     */
    public function contractItems(): HasManyThrough
    {
        return $this->hasManyThrough(ContractItem::class, ServicePackage::class);
    }

    /** @return HasManyThrough<InvoiceItem, ServicePackage, $this> */
    public function invoiceItems(): HasManyThrough
    {
        return $this->hasManyThrough(InvoiceItem::class, ServicePackage::class);
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return Collection<int, Service>
     */
    public static function pickable(): Collection
    {
        return self::query()
            ->active()
            ->whereHas('packages', fn (Builder $query) => $query->where('is_active', true))
            ->with(['packages' => fn (Relation $query) => $query
                ->where('is_active', true)
                ->select(['id', 'service_id', 'name', 'price', 'unit', 'billing_type', 'position'])
                ->with('points:id,service_package_id,label'),
            ])
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'type', 'name']);
    }
}
