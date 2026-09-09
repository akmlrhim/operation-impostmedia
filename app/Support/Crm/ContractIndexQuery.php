<?php

namespace App\Support\Crm;

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Support\ListPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class ContractIndexQuery
{
    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'number' => 'contracts.number',
        'title' => 'contracts.title',
        'start_date' => 'contracts.start_date',
        'value' => 'contracts.value',
        'status' => 'contracts.status',
    ];

    /**
     * @var list<string>
     */
    private const COLUMNS = [
        'contracts.id',
        'contracts.number',
        'contracts.title',
        'contracts.client_id',
        'contracts.type',
        'contracts.status',
        'contracts.billing_cycle',
        'contracts.value',
        'contracts.start_date',
        'contracts.end_date',
        'contracts.signed_date',
        'contracts.created_at',
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

        $matching = function (Builder|Relation $query) use ($status): void {
            $query->when($status !== '', fn ($q) => $q->where('contracts.status', $status));
        };

        $groups = Client::query()
            ->select(['clients.id', 'clients.company_name'])
            ->when($filterClientId, fn (Builder $q) => $q->whereKey($filterClientId))
            ->whereHas('contracts', $matching)
            ->withCount(['contracts' => $matching])
            ->withSum(['contracts as contracts_value' => $matching], 'value')
            ->with(['contracts' => function (Relation $query) use ($matching, $sort, $direction): void {
                $matching($query);

                $query->select(self::COLUMNS)->withCount('invoices');

                if (array_key_exists($sort, self::SORTABLE)) {
                    $query->orderBy(self::SORTABLE[$sort], $direction);
                } else {
                    $query->latest('contracts.id');
                }
            }])
            ->orderBy('clients.company_name')
            ->orderBy('clients.id');

        $page = ListPage::of($groups);

        return [
            'groups' => $page,
            'orphans' => $filterClientId === null && $page->currentPage() === 1
                ? self::orphans($matching, $sort, $direction)
                : null,
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

    /**
     * MoU yang kliennya sudah dihapus tetap harus kelihatan, jadi dikumpulkan
     * jadi satu grup tanpa klien di atas daftar.
     *
     * @param  callable(Builder<Contract>|Relation<Contract, Client, *>): void  $matching
     * @param  'asc'|'desc'  $direction
     * @return array<string, mixed>|null
     */
    private static function orphans(callable $matching, string $sort, string $direction): ?array
    {
        $query = Contract::query()->whereNull('client_id')->withCount('invoices');

        $matching($query);

        if (array_key_exists($sort, self::SORTABLE)) {
            $query->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $query->latest('contracts.id');
        }

        $contracts = (clone $query)->select(self::COLUMNS)->limit(ListPage::PER_PAGE)->get();

        if ($contracts->isEmpty()) {
            return null;
        }

        return [
            'id' => null,
            'company_name' => 'Tanpa klien',
            'contracts' => $contracts,
            'contracts_count' => $query->count(),
            'contracts_value' => (float) $query->sum('value'),
        ];
    }
}
