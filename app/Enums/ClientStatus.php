<?php

namespace App\Enums;

enum ClientStatus: string implements HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Churned = 'churned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
            self::Churned => 'Berhenti',
        };
    }
}
