<?php

namespace App\Support\Crm;

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Support\ListPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContractIndexQuery
{
    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'number' => 'contracts.number',
        'title' => 'contracts.title',
        'client' => 'clients.company_name',
        'start_date' => 'contracts.start_date',
        'value' => 'contracts.value',
        'status' => 'contracts.status',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(Request $request): array
    {
        $status = ContractStatus::tryFrom($request->string('status')->toString())->value ?? '';
        $filterClientId = $request->integer('filter_client') ?: null;
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $contracts = Contract::query()
            ->select('contracts.*')
            ->when($filterClientId, fn ($q) => $q->where('client_id', $filterClientId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with('client:id,company_name')
            ->withCount('invoices');

        if (array_key_exists($sort, self::SORTABLE)) {
            $contracts->leftJoin('clients', 'clients.id', '=', 'contracts.client_id')
                ->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $contracts->latest('contracts.id');
        }

        return [
            'contracts' => ListPage::of($contracts),
            'filters' => [
                'filterClient' => $filterClientId,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'filterClients' => Client::query()
                ->where(fn (Builder $q) => $q
                    ->whereHas('contracts')
                    ->when($filterClientId, fn (Builder $q) => $q->orWhere('clients.id', $filterClientId)))
                ->orderBy('company_name')
                ->get(['id', 'company_name']),
        ];
    }
}
