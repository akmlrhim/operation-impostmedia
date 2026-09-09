<?php

namespace App\Enums;

enum LeadTemperature: string implements HasLabel
{
    case Cold = 'cold';
    case Warm = 'warm';
    case Hot = 'hot';

    public function label(): string
    {
        return match ($this) {
            self::Cold => 'Cold',
            self::Warm => 'Warm',
            self::Hot => 'Hot',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cold => 'Baru masuk, belum ada minat jelas',
            self::Warm => 'Sudah merespons, masih dipertimbangkan',
            self::Hot => 'Siap deal, tinggal menunggu keputusan',
        };
    }
}
