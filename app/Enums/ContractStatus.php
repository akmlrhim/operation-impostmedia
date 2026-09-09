<?php

namespace App\Enums;

enum ContractStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Review = 'review';
    case Sent = 'sent';
    case Signed = 'signed';
    case Active = 'active';
    case Completed = 'completed';
    case Expired = 'expired';
    case Terminated = 'terminated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Review => 'Menunggu Review',
            self::Sent => 'Dikirim ke Klien',
            self::Signed => 'Ditandatangani',
            self::Active => 'Berjalan',
            self::Completed => 'Selesai',
            self::Expired => 'Kedaluwarsa',
            self::Terminated => 'Diputus',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function canBeInvoiced(): bool
    {
        return in_array($this, [self::Signed, self::Active, self::Completed], true);
    }

    public function canBeSigned(): bool
    {
        return in_array($this, [self::Draft, self::Review, self::Sent], true);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Review], true);
    }
}
