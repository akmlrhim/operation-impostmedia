<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ActivityType $type
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 */
class Activity extends Model
{
    use BroadcastsCrmChanges;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Activity>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereNull('completed_at')
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at');
    }
}
