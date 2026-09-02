<?php

namespace App\Http\Controllers\Crm;

use App\Actions\Crm\ConvertLeadToClient;
use App\Enums\LeadSource;
use App\Enums\LeadStageType;
use App\Enums\LeadStatus;
use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BulkIdsRequest;
use App\Http\Requests\Crm\LeadRequest;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\User;
use App\Support\BulkDeleteSummary;
use App\Support\Crm\LeadIndexQuery;
use App\Support\Csv\CsvExport;
use App\Support\EnumOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab')->toString();
        $tab = $tab === 'table' ? 'table' : 'kanban';

        return Inertia::render('leads/index', [
            ...$this->formProps(),
            'tab' => $tab,
            'priorities' => EnumOptions::from(Priority::class),
            'stageOptions' => LeadStage::query()
                ->orderBy('position')
                ->get(['id', 'name', 'color', 'type']),
            ...match ($tab) {
                'table' => LeadIndexQuery::table($request),
                default => ['stages' => LeadIndexQuery::kanban()],
            },
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(): array
    {
        return [
            'users' => User::query()->where('is_active', true)->get(['id', 'name']),
            'priorities' => EnumOptions::from(Priority::class),
            'sources' => EnumOptions::from(LeadSource::class),
            'statuses' => EnumOptions::from(LeadStatus::class),
            'stageTypes' => EnumOptions::from(LeadStageType::class),
        ];
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        $stage = LeadStage::query()->findOrFail($request->integer('lead_stage_id'));

        Lead::create([
            ...$request->validated(),
            'position' => (int) $stage->leads()->max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lead ditambahkan.']);

        return to_route('leads.index');
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lead diperbarui.']);

        return to_route('leads.index');
    }

    public function move(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'lead_stage_id' => ['required', 'exists:lead_stages,id'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $origin = $lead->lead_stage_id;
        $target = (int) $validated['lead_stage_id'];

        DB::transaction(function () use ($lead, $origin, $target, $validated): void {
            $ids = $this->orderedLeadIds($target, $lead->getKey());
            $index = min((int) $validated['position'], count($ids));

            array_splice($ids, $index, 0, [$lead->getKey()]);

            $lead->update(['lead_stage_id' => $target, 'position' => $index]);

            $this->applyPositions($ids, $lead->getKey());

            if ($origin !== $target) {
                $this->applyPositions($this->orderedLeadIds($origin, $lead->getKey()), $lead->getKey());
            }
        });

        return back();
    }

    /**
     * @return list<int>
     */
    private function orderedLeadIds(int $stageId, int $exceptId): array
    {
        $ids = Lead::query()
            ->where('lead_stage_id', $stageId)
            ->whereKeyNot($exceptId)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return array_values(array_map(fn (mixed $id): int => (int) $id, $ids));
    }

    /**
     * @param  list<int>  $ids
     */
    private function applyPositions(array $ids, int $skipId): void
    {
        foreach ($ids as $position => $id) {
            if ($id === $skipId) {
                continue;
            }

            Lead::query()->whereKey($id)->update(['position' => $position]);
        }
    }

    public function convert(Lead $lead, ConvertLeadToClient $converter): RedirectResponse
    {
        if ($lead->status !== LeadStatus::Won && $lead->converted_client_id === null) {
            $lead->update(['status' => LeadStatus::Won]);
        }

        $client = $converter->handle($lead->fresh());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Lead dikonversi jadi klien {$client->company_name}.",
        ]);

        return to_route('clients.show', $client);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lead dihapus.']);

        return back();
    }

    public function destroyBulk(BulkIdsRequest $request): RedirectResponse
    {
        $deleted = Lead::destroy($request->ids());

        Inertia::flash('toast', BulkDeleteSummary::toast($deleted, 0, 'lead', ''));

        return back();
    }

    public function exportCsv(BulkIdsRequest $request): StreamedResponse
    {
        $leads = Lead::query()
            ->whereIn('id', $request->ids())
            ->with('stage:id,name')
            ->orderBy('company_name')
            ->get();

        return CsvExport::download(
            'leads-'.now()->format('Ymd-His').'.csv',
            ['Perusahaan', 'Kontak', 'Telepon', 'Email', 'Stage', 'Prioritas', 'Estimasi Nilai', 'Target Closing', 'Status'],
            $leads->map(fn (Lead $lead): array => [
                $lead->company_name,
                $lead->contact_name ?? '-',
                $lead->phone ?? '-',
                $lead->email ?? '-',
                $lead->stage->name,
                $lead->priority->label(),
                (string) $lead->estimated_value,
                $lead->expected_close_date?->format('Y-m-d') ?? '-',
                $lead->status->label(),
            ]),
        );
    }
}
