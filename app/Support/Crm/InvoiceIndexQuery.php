<?php

namespace App\Support\Crm;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Support\ListPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class InvoiceIndexQuery
{
    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'number' => 'invoices.number',
        'issue_date' => 'invoices.issue_date',
        'due_date' => 'invoices.due_date',
        'total' => 'invoices.total',
        'balance_due' => 'invoices.balance_due',
        'status' => 'invoices.status',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(Request $request): array
    {
        $status = InvoiceStatus::tryFrom($request->string('status')->toString())->value ?? '';
        $filterClientId = $request->integer('filter_client') ?: null;
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $matching = function (Builder|Relation $query) use ($status): void {
            if ($status === InvoiceStatus::Overdue->value) {
                $query->whereIn('invoices.status', InvoiceStatus::outstanding())
                    ->where('invoices.due_date', '<', now()->toDateString());

                return;
            }

            $query->when($status !== '', fn ($q) => $q->where('invoices.status', $status));
        };

        $groups = Client::query()
            ->select(['clients.id', 'clients.company_name'])
            ->when($filterClientId, fn (Builder $q) => $q->whereKey($filterClientId))
            ->whereHas('invoices', $matching)
            ->withCount(['invoices' => $matching])
            ->withSum(['invoices as invoices_total' => $matching], 'total')
            ->withSum(['invoices as invoices_balance_due' => $matching], 'balance_due')
            ->with(['invoices' => function (Relation $query) use ($matching, $sort, $direction): void {
                $matching($query);

                $query->select('invoices.*')->withCount('payments');

                if (array_key_exists($sort, self::SORTABLE)) {
                    $query->orderBy(self::SORTABLE[$sort], $direction);
                } else {
                    $query->latest('invoices.id');
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
                    ->whereHas('invoices')
                    ->when($filterClientId, fn (Builder $q) => $q->orWhere('clients.id', $filterClientId)))
                ->orderBy('company_name')
                ->get(['id', 'company_name']),
            'summary' => [
                'outstanding' => (float) Invoice::query()->outstanding()->sum('balance_due'),
                'overdue' => (float) Invoice::query()->overdue()->sum('balance_due'),
                'draft' => Invoice::query()->where('status', InvoiceStatus::Draft)->count(),
            ],
        ];
    }

    /**
     * Invoice yang kliennya sudah dihapus tetap harus kelihatan, jadi
     * dikumpulkan jadi satu grup tanpa klien di atas daftar.
     *
     * @param  callable(Builder<Invoice>|Relation<Invoice, Client, *>): void  $matching
     * @param  'asc'|'desc'  $direction
     * @return array<string, mixed>|null
     */
    private static function orphans(callable $matching, string $sort, string $direction): ?array
    {
        $query = Invoice::query()->whereNull('client_id')->withCount('payments');

        $matching($query);

        if (array_key_exists($sort, self::SORTABLE)) {
            $query->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $query->latest('invoices.id');
        }

        $invoices = (clone $query)->select('invoices.*')->limit(ListPage::PER_PAGE)->get();

        if ($invoices->isEmpty()) {
            return null;
        }

        return [
            'id' => null,
            'company_name' => 'Tanpa klien',
            'invoices' => $invoices,
            'invoices_count' => $query->count(),
            'invoices_total' => (float) $query->sum('total'),
            'invoices_balance_due' => (float) $query->sum('balance_due'),
        ];
    }
}
