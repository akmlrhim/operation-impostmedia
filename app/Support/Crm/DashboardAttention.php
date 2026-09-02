<?php

namespace App\Support\Crm;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;

class DashboardAttention
{
    /**
     * @return list<array<string, float|int|string>>
     */
    public static function aging(): array
    {
        $today = now()->startOfDay();

        $buckets = [
            ['label' => 'Belum jatuh tempo', 'total' => 0.0, 'count' => 0],
            ['label' => '1–30 hari', 'total' => 0.0, 'count' => 0],
            ['label' => '31–60 hari', 'total' => 0.0, 'count' => 0],
            ['label' => 'Di atas 60 hari', 'total' => 0.0, 'count' => 0],
        ];

        Invoice::query()
            ->outstanding()
            ->get(['due_date', 'balance_due'])
            ->each(function (Invoice $invoice) use (&$buckets, $today): void {
                $late = $invoice->due_date->startOfDay()->diffInDays($today, absolute: false);

                $index = match (true) {
                    $late <= 0 => 0,
                    $late <= 30 => 1,
                    $late <= 60 => 2,
                    default => 3,
                };

                $buckets[$index]['total'] += (float) $invoice->balance_due;
                $buckets[$index]['count']++;
            });

        return $buckets;
    }

    /**
     * @return list<array<string, float|int|string|null>>
     */
    public static function attention(): array
    {
        $items = [];

        $invoices = Invoice::query()
            ->overdue()
            ->with('client:id,company_name')
            ->orderBy('due_date')
            ->limit(6)
            ->get(['id', 'number', 'client_id', 'due_date', 'balance_due']);

        foreach ($invoices as $invoice) {
            $items[] = [
                'kind' => 'invoice',
                'id' => $invoice->id,
                'title' => $invoice->client->company_name,
                'subtitle' => $invoice->number,
                'date' => $invoice->due_date->toDateString(),
                'amount' => (float) $invoice->balance_due,
                'severity' => 'critical',
            ];
        }

        $leads = Lead::query()
            ->overdueFollowUp()
            ->with('stage:id,name')
            ->orderBy('next_follow_up_at')
            ->limit(6)
            ->get(['id', 'company_name', 'lead_stage_id', 'next_follow_up_at']);

        foreach ($leads as $lead) {
            $items[] = [
                'kind' => 'lead',
                'id' => $lead->id,
                'title' => $lead->company_name,
                'subtitle' => 'Follow up · '.$lead->stage->name,
                'date' => $lead->next_follow_up_at->toDateString(),
                'amount' => null,
                'severity' => 'serious',
            ];
        }

        $contracts = Contract::query()
            ->expiringWithin(60)
            ->with('client:id,company_name')
            ->orderBy('end_date')
            ->limit(6)
            ->get(['id', 'number', 'title', 'client_id', 'end_date']);

        foreach ($contracts as $contract) {
            $items[] = [
                'kind' => 'contract',
                'id' => $contract->id,
                'title' => $contract->client->company_name,
                'subtitle' => $contract->title,
                'date' => $contract->end_date->toDateString(),
                'amount' => null,
                'severity' => 'warning',
            ];
        }

        usort($items, fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return array_slice($items, 0, 8);
    }

    /**
     * @return list<array{id: int, type: string, title: string, subject: string|null, user: string|null, at: string|null}>
     */
    public static function activities(): array
    {
        $latest = Activity::query()
            ->with(['user:id,name', 'subject'])
            ->latest()
            ->limit(6)
            ->get();

        $rows = [];

        foreach ($latest as $activity) {
            $rows[] = [
                'id' => $activity->id,
                'type' => $activity->type->label(),
                'title' => $activity->title,
                'subject' => self::subjectLabel($activity),
                'user' => $activity->user?->name,
                'at' => $activity->created_at?->toIso8601String(),
            ];
        }

        return $rows;
    }

    private static function subjectLabel(Activity $activity): ?string
    {
        return match (true) {
            $activity->subject instanceof Lead => $activity->subject->company_name,
            $activity->subject instanceof Client => $activity->subject->company_name,
            $activity->subject instanceof Contract => $activity->subject->number,
            $activity->subject instanceof Invoice => $activity->subject->number,
            default => null,
        };
    }
}
