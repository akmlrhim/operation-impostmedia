<?php

namespace App\Support\Crm;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Support\ListPage;
use Illuminate\Http\Request;

class LeadIndexQuery
{
    public const KANBAN_PER_COLUMN = 50;

    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'company_name' => 'leads.company_name',
        'contact_name' => 'leads.contact_name',
        'stage' => 'lead_stages.position',
        'priority' => 'leads.priority',
        'estimated_value' => 'leads.estimated_value',
        'expected_close_date' => 'leads.expected_close_date',
        'status' => 'leads.status',
    ];

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
                ->with('owner:id,name')
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

        $leads = Lead::query()
            ->when($filterStageId, fn ($q) => $q->where('lead_stage_id', $filterStageId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with(['stage:id,name,color,type', 'owner:id,name']);

        if (array_key_exists($sort, self::SORTABLE)) {
            if ($sort === 'stage') {
                $leads->leftJoin('lead_stages', 'lead_stages.id', '=', 'leads.lead_stage_id')
                    ->select('leads.*');
            }

            $leads->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $leads->latest('leads.id');
        }

        return [
            'leads' => ListPage::of($leads)->through(fn (Lead $lead): array => [
                ...self::mapLead($lead),
                'stage' => $lead->stage?->only(['id', 'name', 'color']),
            ]),
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
    private static function mapStage(LeadStage $stage): array
    {
        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'color' => $stage->color,
            'type' => $stage->type->value,
            'total' => (float) $stage->getAttribute('leads_sum_estimated_value'),
            'count' => (int) $stage->getAttribute('leads_count'),
            'leads' => $stage->leads->map(fn (Lead $lead): array => self::mapLead($lead))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'company_name' => $lead->company_name,
            'contact_name' => $lead->contact_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'source' => $lead->source?->value,
            'estimated_value' => (float) $lead->estimated_value,
            'priority' => $lead->priority->value,
            'status' => $lead->status->value,
            'owner' => $lead->owner?->only(['id', 'name']),
            'owner_id' => $lead->owner_id,
            'lost_reason' => $lead->lost_reason,
            'expected_close_date' => $lead->expected_close_date?->toDateString(),
            'next_follow_up_at' => $lead->next_follow_up_at?->toDateTimeString(),
            'converted_client_id' => $lead->converted_client_id,
            'notes' => $lead->notes,
        ];
    }
}
