<?php

namespace App\Support\Crm;

use App\Enums\InvoiceType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Service;
use App\Support\CompanyProfile;
use App\Support\EnumOptions;

class InvoiceFormOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        return [
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'contracts' => Contract::query()
                ->latest('id')
                ->get(['id', 'number', 'title', 'client_id', 'billing_cycle'])
                ->map(fn (Contract $contract): array => [
                    'id' => $contract->id,
                    'number' => $contract->number,
                    'title' => $contract->title,
                    'client_id' => $contract->client_id,
                    'is_recurring' => $contract->billing_cycle->isRecurring(),
                ]),
            'services' => Service::pickable(),
            'company' => CompanyProfile::all(),
            'types' => EnumOptions::from(InvoiceType::class),
        ];
    }
}
