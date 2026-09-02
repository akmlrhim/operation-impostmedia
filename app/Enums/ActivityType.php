<?php

namespace App\Enums;

enum ActivityType: string implements HasLabel
{
    case Note = 'note';
    case Call = 'call';
    case Meeting = 'meeting';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Visit = 'visit';
    case Proposal = 'proposal';
    case FollowUp = 'follow_up';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Catatan',
            self::Call => 'Telepon',
            self::Meeting => 'Meeting',
            self::Email => 'Email',
            self::WhatsApp => 'WhatsApp',
            self::Visit => 'Kunjungan',
            self::Proposal => 'Kirim Proposal',
            self::FollowUp => 'Follow Up',
            self::Other => 'Lainnya',
        };
    }
}
