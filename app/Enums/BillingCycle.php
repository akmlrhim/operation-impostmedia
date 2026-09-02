<?php

namespace App\Enums;

enum BillingCycle: string implements HasLabel
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Yearly = 'yearly';
    case Milestone = 'milestone';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'Sekali Bayar',
            self::Monthly => 'Bulanan',
            self::Quarterly => 'Per 3 Bulan',
            self::SemiAnnual => 'Per 6 Bulan',
            self::Yearly => 'Tahunan',
            self::Milestone => 'Per Termin',
        };
    }

    public function monthInterval(): ?int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::SemiAnnual => 6,
            self::Yearly => 12,
            self::OneTime, self::Milestone => null,
        };
    }

    public function isRecurring(): bool
    {
        return $this->monthInterval() !== null;
    }
}
