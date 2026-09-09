<?php

namespace App\Enums;

enum LeadStageType: string implements HasLabel
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Dalam Proses',
            self::Won => 'Menang',
            self::Lost => 'Kalah',
        };
    }
}
