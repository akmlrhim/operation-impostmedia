<?php

namespace App\Models;

use App\Enums\DocumentType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property DocumentType $type
 * @property string $period
 * @property string $scope
 * @property string $prefix
 * @property string|null $format
 * @property int|null $padding
 * @property int $last_number
 */
class DocumentSequence extends Model
{
    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'scope' => '',
        'last_number' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'padding' => 'integer',
            'last_number' => 'integer',
        ];
    }

    public static function next(
        DocumentType $type,
        ?string $prefix = null,
        ?CarbonInterface $date = null,
        ?Client $client = null,
    ): string {
        $date = $date === null ? Carbon::now() : Carbon::instance($date);
        $period = static::periodFor($type, $date);
        $scope = static::scopeFor($type, $client);

        return DB::transaction(function () use ($type, $prefix, $date, $period, $scope, $client): string {
            $sequence = static::query()
                ->where('type', $type)
                ->where('period', $period)
                ->where('scope', $scope)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = static::query()->create([
                    'type' => $type,
                    'period' => $period,
                    'scope' => $scope,
                    'prefix' => $prefix ?? $type->defaultPrefix(),
                    'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');

            return $sequence->render($date, $prefix, $client);
        });
    }

    public static function preview(
        DocumentType $type,
        ?string $prefix = null,
        ?CarbonInterface $date = null,
        ?Client $client = null,
    ): string {
        $date = $date === null ? Carbon::now() : Carbon::instance($date);

        $sequence = static::query()->firstOrNew(
            [
                'type' => $type,
                'period' => static::periodFor($type, $date),
                'scope' => static::scopeFor($type, $client),
            ],
            ['prefix' => $prefix ?? $type->defaultPrefix()],
        );

        return $sequence->render($date, $prefix, $client, $sequence->last_number + 1);
    }

    public static function claim(
        DocumentType $type,
        string $number,
        ?string $prefix = null,
        ?CarbonInterface $date = null,
        ?Client $client = null,
    ): void {
        $date = $date === null ? Carbon::now() : Carbon::instance($date);
        $period = static::periodFor($type, $date);
        $scope = static::scopeFor($type, $client);

        DB::transaction(function () use ($type, $number, $prefix, $date, $period, $scope, $client): void {
            $sequence = static::query()
                ->where('type', $type)
                ->where('period', $period)
                ->where('scope', $scope)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = static::query()->create([
                    'type' => $type,
                    'period' => $period,
                    'scope' => $scope,
                    'prefix' => $prefix ?? $type->defaultPrefix(),
                    'last_number' => 0,
                ]);
            }

            if ($sequence->render($date, $prefix, $client, $sequence->last_number + 1) === $number) {
                $sequence->increment('last_number');
            }
        });
    }

    protected function render(Carbon $date, ?string $prefix, ?Client $client, ?int $number = null): string
    {
        $type = $this->type;
        $number ??= $this->last_number;

        return strtr($this->format ?? $type->numberFormat(), [
            '{prefix}' => $prefix ?? $this->prefix,
            '{code}' => static::clientCode($client),
            '{year}' => $date->format('Y'),
            '{yy}' => $date->format('y'),
            '{month}' => $date->format('m'),
            '{day}' => $date->format('d'),
            '{number}' => str_pad(
                (string) $number,
                $this->padding ?? $type->padding(),
                '0',
                STR_PAD_LEFT,
            ),
        ]);
    }

    protected static function periodFor(DocumentType $type, Carbon $date): string
    {
        return $type->resetsMonthly() ? $date->format('Y-m') : '*';
    }

    protected static function scopeFor(DocumentType $type, ?Client $client): string
    {
        return $type->scopedByClient() && $client !== null ? (string) $client->id : '';
    }

    protected static function clientCode(?Client $client): string
    {
        if ($client === null) {
            return 'KLIEN';
        }

        return $client->short_code ?: Client::deriveShortCode($client->company_name);
    }
}
