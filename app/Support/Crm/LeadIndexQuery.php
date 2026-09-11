<?php

namespace App\Support\Crm;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\ServicePackage;
use Illuminate\Http\Request;

class LeadIndexQuery
{
    public const KANBAN_PER_COLUMN = 50;

    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'date_in' => 'leads.date_in',
        'company_name' => 'leads.company_name',
        'industry' => 'leads.industry',
        'vacancy_position' => 'leads.vacancy_position',
        'contact_name' => 'leads.contact_name',
        'region' => 'leads.region',
        'source' => 'leads.source',
        'stage' => 'lead_stages.position',
        'temperature' => 'leads.temperature',
        'estimated_value' => 'leads.estimated_value',
        'last_contact_date' => 'leads.last_contact_date',
        'next_action_date' => 'leads.next_action_date',
        'meeting_date' => 'leads.meeting_date',
        'status' => 'leads.status',
    ];

    /**
     * @var list<string>
     */
    private const RELATIONS = ['stage:id,name,color,type', 'servicePackages:id,name,price', 'latestInvoice', 'assignees:id,name'];

    /**
     * @return list<array<string, mixed>>
     */
    public static function kanban(): array
    {
        return array_values(LeadStage::query()
            ->orderBy('position')
            ->withCount('leads')
            ->withSum('leads', 'estimated_value')
            ->with(['leads' => fn ($q) => $q
                ->with(self::RELATIONS)
                ->orderBy('position')
                ->limit(self::KANBAN_PER_COLUMN),
            ])
            ->get()
            ->map(fn (LeadStage $stage): array => self::mapStage($stage))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function table(Request $request): array
    {
        $status = LeadStatus::tryFrom($request->string('status')->toString())->value ?? '';
        $filterStageId = $request->integer('filter_stage') ?: null;
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        [$withinSort, $withinDirection] = $sort !== '' && $sort !== 'stage' && array_key_exists($sort, self::SORTABLE)
            ? [self::SORTABLE[$sort], $direction]
            : ['leads.position', 'asc'];

        $rows = Lead::query()
            ->when($filterStageId, fn ($q) => $q->where('lead_stage_id', $filterStageId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with(self::RELATIONS)
            ->orderBy($withinSort, $withinDirection)
            ->get();

        $grouped = $rows->groupBy(fn (Lead $lead): string => (string) $lead->lead_stage_id);

        $stages = array_values(LeadStage::query()
            ->orderBy('position')
            ->get(['id', 'name', 'color', 'type'])
            ->filter(fn (LeadStage $stage): bool => $grouped->has((string) $stage->id))
            ->map(fn (LeadStage $stage): array => [
                'id' => $stage->id,
                'name' => $stage->name,
                'color' => $stage->color,
                'type' => $stage->type->value,
                'count' => (int) $grouped->get((string) $stage->id)->count(),
                'leads' => $grouped->get((string) $stage->id)->map(
                    fn (Lead $lead): array => self::tableRow($lead)
                )->all(),
            ])
            ->all());

        if ($grouped->has('')) {
            $leads = $grouped->get('');

            $stages[] = [
                'id' => null,
                'name' => 'Tanpa tahap',
                'color' => null,
                'type' => null,
                'count' => (int) $leads->count(),
                'leads' => $leads->map(fn (Lead $lead): array => self::tableRow($lead))->all(),
            ];
        }

        return [
            'stageTables' => $stages,
            'total' => $rows->count(),
            'filters' => [
                'status' => $status,
                'filterStage' => $filterStageId,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tableRow(Lead $lead): array
    {
        return [
            ...self::card($lead),
            'stage' => $lead->stage?->only(['id', 'name', 'color']),
            'assignees' => $lead->assignees
                ->map(fn ($user): array => $user->only(['id', 'name']))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapStage(LeadStage $stage): array
    {
        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'color' => $stage->color,
            'type' => $stage->type->value,
            'total' => (float) $stage->getAttribute('leads_sum_estimated_value'),
            'count' => (int) $stage->getAttribute('leads_count'),
            'leads' => $stage->leads->map(fn (Lead $lead): array => self::card($lead))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'date_in' => $lead->date_in->toDateString(),
            'company_name' => $lead->company_name,
            'industry' => $lead->industry,
            'vacancy_position' => $lead->vacancy_position,
            'contact_name' => $lead->contact_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'region' => $lead->region,
            'source' => $lead->source?->value,
            'pic' => $lead->pic,
            'assignees' => $lead->assignees
                ->map(fn ($user): array => $user->only(['id', 'name']))
                ->values()
                ->all(),
            'service_packages' => $lead->servicePackages
                ->map(fn (ServicePackage $package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'price' => (float) $package->price,
                ])
                ->values()
                ->all(),
            'last_invoice' => $lead->latestInvoice === null ? null : [
                'id' => $lead->latestInvoice->id,
                'number' => $lead->latestInvoice->number,
                'issue_date' => $lead->latestInvoice->issue_date->toDateString(),
            ],
            'estimated_value' => (float) $lead->estimated_value,
            'last_contact_date' => $lead->last_contact_date?->toDateString(),
            'next_action_date' => $lead->next_action_date?->toDateString(),
            'next_action' => $lead->next_action,
            'meeting_date' => $lead->meeting_date?->toDateString(),
            'temperature' => $lead->temperature->value,
            'notes' => $lead->notes,
            'folder_url' => $lead->folder_url,
            'status' => $lead->status->value,
            'lost_reason' => $lead->lost_reason,
            'converted_client_id' => $lead->converted_client_id,
        ];
    }
}
