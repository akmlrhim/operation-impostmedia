<?php

namespace App\Support\Crm;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DashboardStats
{
    public const TREND_MONTHS = 6;

    /**
     * @var array<int, string>
     */
    public const MONTH_LABELS = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    /**
     * @return array<string, float|int|null>
     */
    public static function stats(CarbonInterface $monthStart): array
    {
        $lastMonth = $monthStart->copy()->subMonth();

        $collected = self::sumPayments($monthStart, $monthStart->copy()->endOfMonth());
        $collectedPrev = self::sumPayments($lastMonth, $lastMonth->copy()->endOfMonth());

        $issued = self::sumIssued($monthStart, $monthStart->copy()->endOfMonth());
        $issuedPrev = self::sumIssued($lastMonth, $lastMonth->copy()->endOfMonth());

        $outstanding = (float) Invoice::query()->outstanding()->sum('balance_due');
        $overdue = (float) Invoice::query()->overdue()->sum('balance_due');

        return [
            'collected' => $collected,
            'collectedChange' => self::percentChange($collected, $collectedPrev),
            'issued' => $issued,
            'issuedChange' => self::percentChange($issued, $issuedPrev),
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'overdueCount' => Invoice::query()->overdue()->count(),
            'pipeline' => (float) Lead::query()->open()->sum('estimated_value'),
            'openLeads' => Lead::query()->open()->count(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, year: string, issued: float, collected: float}>
     */
    public static function trend(CarbonInterface $monthStart): array
    {
        $from = $monthStart->copy()->subMonths(self::TREND_MONTHS - 1);

        $issued = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft, InvoiceStatus::Void])
            ->whereDate('issue_date', '>=', $from)
            ->get(['issue_date', 'total'])
            ->groupBy(fn (Invoice $invoice): string => $invoice->issue_date->format('Y-m'))
            ->map(fn (Collection $rows): float => (float) $rows->sum('total'));

        $collected = Payment::query()
            ->whereDate('paid_at', '>=', $from)
            ->get(['paid_at', 'amount'])
            ->groupBy(fn (Payment $payment): string => $payment->paid_at->format('Y-m'))
            ->map(fn (Collection $rows): float => (float) $rows->sum('amount'));

        $months = [];

        for ($ago = self::TREND_MONTHS - 1; $ago >= 0; $ago--) {
            $month = $monthStart->copy()->subMonths($ago);
            $key = $month->format('Y-m');

            $months[] = [
                'key' => $key,
                'label' => self::MONTH_LABELS[(int) $month->month],
                'year' => $month->format('Y'),
                'issued' => $issued->get($key, 0.0),
                'collected' => $collected->get($key, 0.0),
            ];
        }

        return $months;
    }

    private static function sumPayments(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Payment::query()->whereBetween('paid_at', [$from, $to])->sum('amount');
    }

    private static function sumIssued(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft, InvoiceStatus::Void])
            ->whereBetween('issue_date', [$from, $to])
            ->sum('total');
    }

    private static function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
