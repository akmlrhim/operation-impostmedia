<?php

namespace App\Support\Crm;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Lead;
use App\Models\ServicePackage;

class LeadDetail
{
    /**
     * @return array<string, mixed>
     */
    public static function props(Lead $lead): array
    {
        $lead->load([
            'stage:id,name,color,type',
            'servicePackages:id,service_id,name,price,unit',
            'servicePackages.service:id,name,type',
            'latestInvoice',
            'convertedClient:id,company_name',
            'attachments.uploader:id,name',
            'assignees:id,name',
        ]);

        return [
            'lead' => [
                ...LeadIndexQuery::card($lead),
                'stage' => $lead->stage?->only(['id', 'name', 'color']),
                'assignees' => $lead->assignees->map(fn ($user): array => $user->only(['id', 'name']))->values()->all(),
                'services' => $lead->servicePackages
                    ->map(fn (ServicePackage $package): array => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => (float) $package->price,
                        'unit' => $package->unit,
                        'service_name' => $package->service?->name,
                    ])
                    ->values()
                    ->all(),
                'converted_client' => $lead->convertedClient?->only(['id', 'company_name']),
                'attachments' => $lead->attachments
                    ->map(fn (Attachment $attachment): array => [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'created_at' => $attachment->created_at?->toIso8601String(),
                        'uploader' => $attachment->uploader?->only(['id', 'name']),
                    ])
                    ->all(),
            ],
            'timeline' => self::timeline($lead),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function timeline(Lead $lead): array
    {
        return array_values($lead->activities()
            ->with('user:id,name')
            ->reorder()
            ->orderByRaw('COALESCE(scheduled_at, created_at) desc')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'type' => $activity->type->value,
                'type_label' => $activity->type->label(),
                'title' => $activity->title,
                'description' => $activity->description,
                'scheduled_at' => $activity->scheduled_at?->toIso8601String(),
                'completed_at' => $activity->completed_at?->toIso8601String(),
                'created_at' => $activity->created_at?->toIso8601String(),
                'user' => $activity->user?->only(['id', 'name']),
            ])
            ->all());
    }
}
