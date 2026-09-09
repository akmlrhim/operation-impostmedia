<?php

namespace App\Support\Crm;

use App\Enums\InvoiceStatus;
use App\Enums\LeadTemperature;
use App\Models\Activity;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class DashboardStats
{
    public const TREND_MONTHS = 6;

    public const PERIOD_OPTIONS = 36;

    /**
     * @var array<int, string>
     */
    public const MONTH_LABELS = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    public static function monthLabel(CarbonInterface $month): string
    {
        return self::MONTH_LABELS[(int) $month->month].' '.$month->year;
    }

    public static function resolveMonth(?string $value): CarbonInterface
    {
        $current = now()->startOfMonth();

        if ($value === null || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            return $current;
        }

        $month = Date::parse($value.'-01')->startOfMonth();

        return $month->greaterThan($current) ? $current : $month;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function months(): array
    {
        $current = now()->startOfMonth();
        $earliest = self::earliestRecord() ?? $current;
        $oldest = $current->copy()->subMonths(self::PERIOD_OPTIONS - 1);

        if ($earliest->lessThan($oldest)) {
            $earliest = $oldest;
        }

        $months = [];

        for ($month = $current; $month->greaterThanOrEqualTo($earliest); $month = $month->subMonth()) {
            $months[] = [
                'value' => $month->format('Y-m'),
                'label' => self::monthLabel($month),
            ];
        }

        return $months;
    }

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
     * @return list<array{value: string, label: string, description: string, count: int, total: float}>
     */
    public static function temperature(): array
    {
        $open = Lead::query()
            ->open()
            ->selectRaw('temperature, count(*) as leads_count, sum(estimated_value) as leads_total')
            ->groupBy('temperature')
            ->get()
            ->keyBy(fn (Lead $lead): string => $lead->temperature->value);

        $buckets = [];

        foreach ([LeadTemperature::Hot, LeadTemperature::Warm, LeadTemperature::Cold] as $level) {
            $row = $open->get($level->value);

            $buckets[] = [
                'value' => $level->value,
                'label' => $level->label(),
                'description' => $level->description(),
                'count' => (int) ($row->leads_count ?? 0),
                'total' => (float) ($row->leads_total ?? 0),
            ];
        }

        return $buckets;
    }

    /**
     * @return list<array{key: string, label: string, year: string, issued: float, collected: float}>
     */
    public static function trend(CarbonInterface $monthStart): array
    {
        $from = $monthStart->copy()->subMonths(self::TREND_MONTHS - 1);

        $issued = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft, InvoiceStatus::Void])
            ->where('issue_date', '>=', $from->toDateString())
            ->get(['issue_date', 'total'])
            ->groupBy(fn (Invoice $invoice): string => $invoice->issue_date->format('Y-m'))
            ->map(fn (Collection $rows): float => (float) $rows->sum('total'));

        $collected = Payment::query()
            ->where('paid_at', '>=', $from->toDateString())
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

    private static function earliestRecord(): ?CarbonInterface
    {
        $earliest = null;

        $candidates = [
            Invoice::query()->min('issue_date'),
            Payment::query()->min('paid_at'),
            Activity::query()->min('created_at'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $month = Date::parse($candidate)->startOfMonth();

            if ($earliest === null || $month->lessThan($earliest)) {
                $earliest = $month;
            }
        }

        return $earliest;
    }

    private static function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
