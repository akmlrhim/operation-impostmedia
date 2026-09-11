<?php

namespace App\Support\Finance;

use App\Enums\FinanceTransactionType;
use App\Models\FinanceTransaction;
use App\Support\ListPage;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class FinanceIndexQuery
{
    /**
     * @var array<string, string>
     */
    private const SORTABLE = [
        'transaction_date' => 'finance_transactions.transaction_date',
        'category' => 'finance_transactions.category',
        'amount' => 'finance_transactions.amount',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(Request $request, CarbonInterface $from, CarbonInterface $to): array
    {
        $type = FinanceTransactionType::tryFrom($request->string('type')->toString())->value ?? '';
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $query = FinanceTransaction::query()
            ->whereBetween('transaction_date', [$from, $to])
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q2) => $q2->where('category', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
            ))
            ->with('recorder:id,name');

        if (array_key_exists($sort, self::SORTABLE)) {
            $query->orderBy(self::SORTABLE[$sort], $direction);
        } else {
            $query->orderByDesc('finance_transactions.transaction_date')->orderByDesc('finance_transactions.id');
        }

        return [
            'transactions' => ListPage::of($query),
            'filters' => [
                'type' => $type,
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ];
    }
}
