<?php

namespace App\Enums;

enum ContractType: string implements HasLabel
{
    case Mou = 'mou';
    case Spk = 'spk';
    case Nda = 'nda';
    case Addendum = 'addendum';
    case Contract = 'contract';

    public function label(): string
    {
        return match ($this) {
            self::Mou => 'MoU / Nota Kesepahaman',
            self::Spk => 'SPK / Surat Perintah Kerja',
            self::Nda => 'NDA / Perjanjian Kerahasiaan',
            self::Addendum => 'Addendum',
            self::Contract => 'Perjanjian Kerja Sama',
        };
    }
}
