<?php

namespace App\Enums;

enum FinanceTransactionType: string implements HasLabel
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Debit',
            self::Expense => 'Kredit',
        };
    }
}
