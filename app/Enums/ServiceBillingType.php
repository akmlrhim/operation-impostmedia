<?php

namespace App\Enums;

enum ServiceBillingType: string implements HasLabel
{
    case OneTime = 'one_time';
    case MonthlyRetainer = 'monthly_retainer';
    case PerProject = 'per_project';
    case Hourly = 'hourly';
    case Performance = 'performance';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'Sekali Bayar',
            self::MonthlyRetainer => 'Retainer Bulanan',
            self::PerProject => 'Per Proyek',
            self::Hourly => 'Per Jam',
            self::Performance => 'Berbasis Performa',
        };
    }
}
