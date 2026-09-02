<?php

namespace App\Enums;

enum ServiceType: string implements HasLabel
{
    case Umkm = 'umkm';
    case Brand = 'brand';

    public function label(): string
    {
        return match ($this) {
            self::Umkm => 'UMKM',
            self::Brand => 'Brand',
        };
    }
}
