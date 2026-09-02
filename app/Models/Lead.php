<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Priority;
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
 * @property string $company_name
 * @property string $contact_name
 * @property LeadStatus $status
 * @property Priority $priority
 * @property LeadSource|null $source
 * @property string $estimated_value
 * @property int $position
 * @property Carbon|null $expected_close_date
 * @property Carbon|null $converted_at
 * @property Carbon|null $next_follow_up_at
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
            'priority' => Priority::class,
            'source' => LeadSource::class,
            'estimated_value' => 'decimal:2',
            'position' => 'integer',
            'expected_close_date' => 'date',
            'converted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LeadStage, $this> */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class, 'lead_stage_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Client, $this> */
    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
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
    public function scopeOverdueFollowUp(Builder $query): void
    {
        $query->open()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now());
    }
}
