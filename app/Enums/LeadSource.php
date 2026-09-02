<?php

namespace App\Enums;

enum LeadSource: string implements HasLabel
{
    case Referral = 'referral';
    case ExistingClient = 'existing_client';
    case Website = 'website';
    case SocialMedia = 'social_media';
    case Ads = 'ads';
    case Tender = 'tender';
    case Event = 'event';
    case ColdOutreach = 'cold_outreach';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Referral => 'Referral',
            self::ExistingClient => 'Klien Lama',
            self::Website => 'Website',
            self::SocialMedia => 'Media Sosial',
            self::Ads => 'Iklan',
            self::Tender => 'Tender',
            self::Event => 'Event / Pameran',
            self::ColdOutreach => 'Cold Call / Email',
            self::Other => 'Lainnya',
        };
    }
}
