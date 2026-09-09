<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Models\Concerns\BroadcastsCrmChanges;
use App\Models\Concerns\HasActivities;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $lead_stage_id
 * @property-read LeadStage|null $stage
 * @property Carbon $date_in
 * @property string $company_name
 * @property string|null $industry
 * @property string $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $region
 * @property LeadSource|null $source
 * @property string|null $pic
 * @property string|null $pic_impost
 * @property-read Collection<int, ServicePackage> $servicePackages
 * @property-read Invoice|null $latestInvoice
 * @property string $estimated_value
 * @property Carbon|null $last_contact_date
 * @property Carbon|null $next_action_date
 * @property string|null $next_action
 * @property LeadTemperature $temperature
 * @property string|null $notes
 * @property string|null $folder_url
 * @property LeadStatus $status
 * @property int $position
 */
class Lead extends Model
{
    use BroadcastsCrmChanges, HasActivities, HasAttachments, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'temperature' => LeadTemperature::class,
            'source' => LeadSource::class,
            'estimated_value' => 'decimal:2',
            'position' => 'integer',
            'date_in' => 'date',
            'last_contact_date' => 'date',
            'next_action_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if ($lead->getAttribute('date_in') === null) {
                $lead->setAttribute('date_in', now()->toDateString());
            }
        });
    }

    /** @return BelongsTo<LeadStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class, 'lead_stage_id');
    }

    /** @return BelongsToMany<ServicePackage, $this> */
    public function servicePackages(): BelongsToMany
    {
        return $this->belongsToMany(ServicePackage::class)
            ->withPivot('position')
            ->orderBy('lead_service_package.position')
            ->orderBy('service_packages.id');
    }

    /** @return BelongsTo<Client, $this> */
    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    /** @return HasOneThrough<Invoice, Client, $this> */
    public function latestInvoice(): HasOneThrough
    {
        return $this->hasOneThrough(
            Invoice::class,
            Client::class,
            'id',
            'client_id',
            'converted_client_id',
            'id',
        )->latestOfMany();
    }

    /** @return HasMany<Contract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @param  Builder<Lead>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', LeadStatus::Open);
    }

    /**
     * @param  Builder<Lead>  $query
     */
    public function scopeOverdueNextAction(Builder $query): void
    {
        $query->open()
            ->whereNotNull('next_action_date')
            ->whereDate('next_action_date', '<=', now());
    }
}
