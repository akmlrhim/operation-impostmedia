<?php

namespace App\Support\Crm;

use App\Models\Client;
use App\Support\ListPage;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientIndexQuery
{
    private const DOCUMENT_PREVIEW = 5;

    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'company_name' => 'clients.company_name',
        'contact_name' => 'clients.contact_name',
        'account_manager' => 'users.name',
        'status' => 'clients.status',
    ];

    /**
     * @param  'asc'|'desc'  $direction
     * @return LengthAwarePaginator<int, Client>
     */
    public static function build(?int $filterClientId, string $status, string $sort, string $direction): LengthAwarePaginator
    {
        $query = Client::query()
            ->select('clients.*')
            ->when($filterClientId, fn ($q) => $q->whereKey($filterClientId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with([
                'accountManager:id,name',
                'contracts' => fn ($q) => $q
                    ->select(['id', 'client_id', 'number', 'title', 'value', 'status'])
                    ->latest('id')
                    ->limit(self::DOCUMENT_PREVIEW),
                'invoices' => fn ($q) => $q
                    ->select(['id', 'client_id', 'number', 'total', 'balance_due', 'due_date', 'status'])
                    ->latest('id')
                    ->limit(self::DOCUMENT_PREVIEW),
            ])
            ->withCount(['contracts', 'invoices']);

        if (array_key_exists($sort, self::SORTABLE)) {
            if ($sort === 'account_manager') {
                $query->leftJoin('users', 'users.id', '=', 'clients.account_manager_id');
            }

            $query->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $query->latest('clients.id');
        }

        return ListPage::of($query);
    }
}
