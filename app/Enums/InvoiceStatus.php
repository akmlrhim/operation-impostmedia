<?php

namespace App\Enums;

enum InvoiceStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Sent => 'Terkirim',
            self::PartiallyPaid => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Overdue => 'Jatuh Tempo',
            self::Void => 'Dibatalkan',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isOutstanding(): bool
    {
        return in_array($this, self::outstanding(), true);
    }

    /**
     * Tagihan yang masih ditunggu pembayarannya.
     *
     * @return list<self>
     */
    public static function outstanding(): array
    {
        return [self::Sent, self::PartiallyPaid, self::Overdue];
    }
}
