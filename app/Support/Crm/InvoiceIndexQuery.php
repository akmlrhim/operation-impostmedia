<?php

namespace App\Support\Crm;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Support\ListPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InvoiceIndexQuery
{
    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'number' => 'invoices.number',
        'client' => 'clients.company_name',
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

        $invoices = Invoice::query()
            ->select('invoices.*')
            ->when($filterClientId, fn ($q) => $q->where('client_id', $filterClientId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with('client:id,company_name')
            ->withCount('payments');

        if (array_key_exists($sort, self::SORTABLE)) {
            $invoices->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
                ->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $invoices->latest('invoices.id');
        }

        return [
            'invoices' => ListPage::of($invoices),
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
}
