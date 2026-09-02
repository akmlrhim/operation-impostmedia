<?php

namespace App\Enums;

enum PaymentMethod: string implements HasLabel
{
    case Transfer = 'transfer';
    case VirtualAccount = 'virtual_account';
    case Cash = 'cash';
    case Check = 'check';
    case CreditCard = 'credit_card';
    case EWallet = 'e_wallet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transfer Bank',
            self::VirtualAccount => 'Virtual Account',
            self::Cash => 'Tunai',
            self::Check => 'Cek / Giro',
            self::CreditCard => 'Kartu Kredit',
            self::EWallet => 'E-Wallet',
            self::Other => 'Lainnya',
        };
    }
}
