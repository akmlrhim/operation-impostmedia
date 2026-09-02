<?php

namespace App\Enums;

enum InvoiceType: string implements HasLabel
{
    case Invoice = 'invoice';
    case Proforma = 'proforma';
    case DownPayment = 'down_payment';
    case Final = 'final';
    case Recurring = 'recurring';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Invoice',
            self::Proforma => 'Proforma Invoice',
            self::DownPayment => 'Invoice DP',
            self::Final => 'Invoice Pelunasan',
            self::Recurring => 'Invoice Retainer',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Invoice, self::Recurring => 'INV',
            self::Proforma => 'PRO',
            self::DownPayment => 'INV-DP',
            self::Final => 'INV-FIN',
        };
    }
}
