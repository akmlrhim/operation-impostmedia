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

    /**
     * Kolom boleh dihapus walau masih berisi kartu; leadnya pindah ke kolom
     * paling kiri yang tersisa, dan hanya jadi tanpa kolom kalau papan habis.
     */
    protected static function booted(): void
    {
        static::deleting(function (LeadStage $stage): void {
            $fallback = static::query()
                ->whereKeyNot($stage->getKey())
                ->orderBy('position')
                ->first();

            Lead::withTrashed()
                ->where('lead_stage_id', $stage->getKey())
                ->update(['lead_stage_id' => $fallback?->getKey()]);
        });
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class)->orderBy('position');
    }
}
