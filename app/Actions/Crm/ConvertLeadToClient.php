<?php

namespace App\Actions\Crm;

use App\Enums\LeadStageType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadStage;
use Illuminate\Support\Facades\DB;

class ConvertLeadToClient
{
    public function handle(Lead $lead): Client
    {
        if ($lead->converted_client_id !== null) {
            return $lead->convertedClient;
        }

        return DB::transaction(function () use ($lead): Client {
            $client = Client::create([
                'short_code' => Client::generateShortCode($lead->company_name),
                'company_name' => $lead->company_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'city' => $lead->region,
                'contact_name' => $lead->contact_name,
                'notes' => $lead->notes,
            ]);

            $attributes = [
                'status' => LeadStatus::Won,
                'converted_client_id' => $client->id,
            ];

            if ($lead->stage->type !== LeadStageType::Won) {
                $wonStage = LeadStage::query()->where('type', LeadStageType::Won)->first();

                if ($wonStage !== null) {
                    $attributes['lead_stage_id'] = $wonStage->id;
                }
            }

            $lead->update($attributes);

            return $client;
        });
    }
}
