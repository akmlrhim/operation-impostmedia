<?php

namespace App\Enums;

enum DocumentType: string implements HasLabel
{
    case Contract = 'contract';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Kontrak',
            self::Invoice => 'Invoice',
        };
    }

    public function defaultPrefix(): string
    {
        return match ($this) {
            self::Contract => 'MOU',
            self::Invoice => 'INV',
        };
    }

    public function numberFormat(): string
    {
        return match ($this) {
            self::Contract => 'IM-MOU-{day}{month}-PRJ-{number}',
            self::Invoice => 'IM-{code}/{number}/{month}/{yy}',
        };
    }

    public function padding(): int
    {
        return 3;
    }

    public function resetsMonthly(): bool
    {
        return false;
    }

    public function scopedByClient(): bool
    {
        return $this === self::Invoice;
    }
}
