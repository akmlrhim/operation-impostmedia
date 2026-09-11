<?php

namespace App\Support\Crm;

use App\Enums\BillingCycle;
use App\Enums\ContractType;
use App\Models\Client;
use App\Models\Service;
use App\Support\Ai\Groq;
use App\Support\CompanyProfile;
use App\Support\EnumOptions;

class ContractFormOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        return [
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'services' => Service::pickable(),
            'company' => CompanyProfile::all(),
            'types' => EnumOptions::from(ContractType::class),
            'billingCycles' => EnumOptions::from(BillingCycle::class),
            'aiScopePoints' => Groq::configured(),
            'users' => UserOptions::assignable(),
        ];
    }
}
