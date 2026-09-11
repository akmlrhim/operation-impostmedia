<?php

namespace App\Support\Finance;

use App\Enums\FinanceTransactionType;
use App\Models\FinanceTransaction;
use App\Support\Crm\DashboardStats;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FinanceStats
{
    /**
     * Rentang dikelompokkan per bulan begitu melebihi kira-kira dua bulan,
     * supaya grafik rentang panjang tidak menampilkan ratusan batang harian.
     */
    private const GROUP_BY_MONTH_ABOVE_DAYS = 62;

    /**
     * @return array{income: float, expense: float, net: float}
     */
    public static function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $income = (float) FinanceTransaction::query()
            ->income()
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $expense = (float) FinanceTransaction::query()
            ->expense()
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    /**
     * @return list<array{key: string, label: string, income: float, expense: float}>
     */
    public static function series(CarbonInterface $from, CarbonInterface $to): array
    {
        $byMonth = $from->diffInDays($to) > self::GROUP_BY_MONTH_ABOVE_DAYS;
        $format = $byMonth ? 'Y-m' : 'Y-m-d';

        $rows = FinanceTransaction::query()
            ->whereBetween('transaction_date', [$from, $to])
            ->get(['type', 'amount', 'transaction_date']);

        $grouped = $rows->groupBy(fn (FinanceTransaction $row): string => $row->transaction_date->format($format));

        $points = [];
        $cursor = $byMonth ? $from->copy()->startOfMonth() : $from->copy();
        $end = $byMonth ? $to->copy()->startOfMonth() : $to->copy();

        while ($cursor->lessThanOrEqualTo($end) && count($points) <= 366) {
            $key = $cursor->format($format);
            $bucket = $grouped->get($key, new Collection);

            $points[] = [
                'key' => $key,
                'label' => $byMonth ? DashboardStats::monthLabel($cursor) : $cursor->format('d M'),
                'income' => (float) $bucket->filter(
                    fn (FinanceTransaction $row): bool => $row->type === FinanceTransactionType::Income
                )->sum('amount'),
                'expense' => (float) $bucket->filter(
                    fn (FinanceTransaction $row): bool => $row->type === FinanceTransactionType::Expense
                )->sum('amount'),
            ];

            $cursor = $byMonth ? $cursor->addMonth() : $cursor->addDay();
        }

        return $points;
    }
}
