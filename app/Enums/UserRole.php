<?php

namespace App\Enums;

enum UserRole: string implements HasLabel
{
    case Superuser = 'superuser';
    case Manager = 'manager';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Superuser => 'Superuser',
            self::Manager => 'Manager',
            self::Member => 'Member',
        };
    }

    public static function default(): self
    {
        return self::Member;
    }
}
