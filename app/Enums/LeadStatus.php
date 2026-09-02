<?php

namespace App\Enums;

enum LeadStatus: string implements HasLabel
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Berjalan',
            self::Won => 'Deal',
            self::Lost => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::Won => 'green',
            self::Lost => 'red',
        };
    }
}
