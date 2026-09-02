<?php

namespace App\Models;

use App\Enums\LeadStageType;
use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $position
 * @property LeadStageType $type
 */
class LeadStage extends Model
{
    use BroadcastsCrmChanges;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LeadStageType::class,
            'position' => 'integer',
        ];
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class)->orderBy('position');
    }
}
