<?php

namespace App\Support\Crm;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class AgendaCalendar
{
    /**
     * @var list<string>
     */
    public const TYPES = ['lead', 'invoice', 'contract', 'activity'];

    public static function resolveMonth(?string $value): CarbonInterface
    {
        if ($value === null || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            return now()->startOfMonth();
        }

        return Date::parse($value.'-01')->startOfMonth();
    }

    /**
     * @param  list<string>  $types
     * @return list<array{date: string, kind: string, title: string, subtitle: string, url: string}>
     */
    public static function events(CarbonInterface $month, array $types, ?int $userId = null): array
    {
        $from = $month->copy()->startOfMonth();
        $until = $month->copy()->endOfMonth();

        $events = [
            ...(in_array('lead', $types, true) ? self::leads($from, $until, $userId) : []),
            ...(in_array('invoice', $types, true) ? self::invoices($from, $until, $userId) : []),
            ...(in_array('contract', $types, true) ? self::contracts($from, $until, $userId) : []),
            ...(in_array('activity', $types, true) ? self::activities($from, $until, $userId) : []),
        ];

        usort($events, fn (array $a, array $b): int => [$a['date'], $a['title']] <=> [$b['date'], $b['title']]);

        return $events;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private static function onlyMine(Builder $query, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $query->where(function (Builder $inner) use ($userId): void {
            $inner->whereHas('assignees', fn (Builder $q) => $q->whereKey($userId))
                ->orWhere('created_by', $userId);
        });
    }

    /**
     * @return list<array{date: string, kind: string, title: string, subtitle: string, url: string}>
     */
    private static function leads(CarbonInterface $from, CarbonInterface $until, ?int $userId): array
    {
        $query = Lead::query()
            ->open()
            ->whereNotNull('next_action_date')
            ->whereBetween('next_action_date', [$from->toDateString(), $until->toDateString()]);

        self::onlyMine($query, $userId);

        return array_values($query
            ->orderBy('next_action_date')
            ->get(['id', 'company_name', 'next_action', 'next_action_date'])
            ->map(fn (Lead $lead): array => [
                'date' => $lead->next_action_date->toDateString(),
                'kind' => 'lead',
                'title' => $lead->company_name,
                'subtitle' => $lead->next_action ?: 'Tindak lanjut',
                'url' => route('leads.show', $lead),
            ])
            ->all());
    }

    /**
     * @return list<array{date: string, kind: string, title: string, subtitle: string, url: string}>
     */
    private static function invoices(CarbonInterface $from, CarbonInterface $until, ?int $userId): array
    {
        $query = Invoice::query()
            ->outstanding()
            ->with('client:id,company_name')
            ->whereBetween('due_date', [$from->toDateString(), $until->toDateString()]);

        self::onlyMine($query, $userId);

        return array_values($query
            ->orderBy('due_date')
            ->get(['id', 'number', 'client_id', 'due_date'])
            ->map(fn (Invoice $invoice): array => [
                'date' => $invoice->due_date->toDateString(),
                'kind' => 'invoice',
                'title' => $invoice->number,
                'subtitle' => 'Jatuh tempo · '.($invoice->client->company_name ?? 'Tanpa klien'),
                'url' => route('invoices.show', $invoice),
            ])
            ->all());
    }

    /**
     * @return list<array{date: string, kind: string, title: string, subtitle: string, url: string}>
     */
    private static function contracts(CarbonInterface $from, CarbonInterface $until, ?int $userId): array
    {
        $query = Contract::query()
            ->with('client:id,company_name')
            ->where(function (Builder $inner) use ($from, $until): void {
                $inner->whereBetween('start_date', [$from->toDateString(), $until->toDateString()])
                    ->orWhereBetween('end_date', [$from->toDateString(), $until->toDateString()]);
            });

        self::onlyMine($query, $userId);

        $events = [];

        foreach ($query->get(['id', 'number', 'title', 'client_id', 'start_date', 'end_date']) as $contract) {
            $client = $contract->client->company_name ?? 'Tanpa klien';

            if ($contract->start_date !== null && $contract->start_date->betweenIncluded($from, $until)) {
                $events[] = [
                    'date' => $contract->start_date->toDateString(),
                    'kind' => 'contract-start',
                    'title' => $contract->number,
                    'subtitle' => 'MoU mulai · '.$client,
                    'url' => route('contracts.show', $contract),
                ];
            }

            if ($contract->end_date !== null && $contract->end_date->betweenIncluded($from, $until)) {
                $events[] = [
                    'date' => $contract->end_date->toDateString(),
                    'kind' => 'contract-end',
                    'title' => $contract->number,
                    'subtitle' => 'MoU berakhir · '.$client,
                    'url' => route('contracts.show', $contract),
                ];
            }
        }

        return $events;
    }

    /**
     * @return list<array{date: string, kind: string, title: string, subtitle: string, url: string}>
     */
    private static function activities(CarbonInterface $from, CarbonInterface $until, ?int $userId): array
    {
        $query = Activity::query()
            ->with(['subject', 'user:id,name'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$from, $until]);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return array_values($query
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Activity $activity): array => [
                'date' => $activity->scheduled_at?->toDateString() ?? $from->toDateString(),
                'kind' => 'activity',
                'title' => $activity->title,
                'subtitle' => trim($activity->type->label().' · '.(self::subjectLabel($activity) ?? 'Tanpa data terkait'), ' ·'),
                'url' => self::subjectUrl($activity),
            ])
            ->all());
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

    private static function subjectUrl(Activity $activity): string
    {
        return match (true) {
            $activity->subject instanceof Lead => route('leads.show', $activity->subject),
            $activity->subject instanceof Client => route('clients.show', $activity->subject),
            $activity->subject instanceof Contract => route('contracts.show', $activity->subject),
            $activity->subject instanceof Invoice => route('invoices.show', $activity->subject),
            default => route('dashboard'),
        };
    }
}
