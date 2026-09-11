import { Link, router } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight, List } from 'lucide-react';
import { useState } from 'react';
import { AgendaList } from '@/components/crm/agenda/agenda-list';
import { MonthGrid, kindDots, todayKey } from '@/components/crm/agenda/month-grid';
import type { AgendaEvent } from '@/components/crm/agenda/month-grid';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useRealtime } from '@/hooks/use-realtime';
import { formatLongDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type AgendaView = 'calendar' | 'list';

const views: { value: AgendaView; label: string; icon: typeof List }[] = [
  { value: 'calendar', label: 'Kalender', icon: CalendarDays },
  { value: 'list', label: 'Daftar', icon: List },
];

const typeLabels: { value: string; label: string; dot: string }[] = [
  { value: 'lead', label: 'Tindak lanjut', dot: kindDots.lead },
  { value: 'invoice', label: 'Jatuh tempo', dot: kindDots.invoice },
  { value: 'contract', label: 'Masa MoU', dot: kindDots['contract-start'] },
  { value: 'activity', label: 'Aktivitas', dot: kindDots.activity },
];

function shiftMonth(month: string, step: number): string {
  const [year, monthNumber] = month.split('-').map(Number);
  const shifted = new Date(year, monthNumber - 1 + step, 1);

  return `${shifted.getFullYear()}-${String(shifted.getMonth() + 1).padStart(2, '0')}`;
}

function thisMonth(): string {
  const now = new Date();

  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

export function AgendaSection({
  month,
  period,
  types,
  mine,
  view,
  events,
}: {
  month: string;
  period: string;
  types: string[];
  mine: boolean;
  view: AgendaView;
  events: AgendaEvent[];
}) {
  useRealtime(['leads', 'invoices', 'contracts', 'activities'], ['events']);

  const today = todayKey();
  const [selected, setSelected] = useState(() =>
    today.startsWith(month) ? today : (events[0]?.date ?? `${month}-01`),
  );

  const selectedEvents = events.filter((event) => event.date === selected);

  function go(next: { month?: string; types?: string[]; mine?: boolean; view?: AgendaView }) {
    const nextTypes = next.types ?? types;

    router.get(
      dashboard().url,
      {
        month: next.month ?? month,
        types: nextTypes.length === typeLabels.length ? undefined : nextTypes.join(','),
        mine: (next.mine ?? mine) ? 1 : undefined,
        view: (next.view ?? view) === 'list' ? 'list' : undefined,
      },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function toggleType(value: string) {
    const next = types.includes(value) ? types.filter((type) => type !== value) : [...types, value];

    go({ types: next.length === 0 ? typeLabels.map((type) => type.value) : next });
  }

  return (
    <Card className="py-0">
      <div className="flex flex-wrap items-center gap-2 border-b border-border px-4 pt-3 pb-2">
        <div className="mr-auto">
          <h2 className="text-lg font-semibold">Agenda</h2>
          <p className="text-xs text-muted-foreground">
            Tindak lanjut, jatuh tempo tagihan, masa MoU, dan aktivitas
          </p>
        </div>

        <div className="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon-sm"
            aria-label="Bulan sebelumnya"
            onClick={() => go({ month: shiftMonth(month, -1) })}
          >
            <ChevronLeft className="size-4" />
          </Button>

          <span className="min-w-28 text-center num text-[0.8125rem] font-medium">{period}</span>

          <Button
            variant="outline"
            size="icon-sm"
            aria-label="Bulan berikutnya"
            onClick={() => go({ month: shiftMonth(month, 1) })}
          >
            <ChevronRight className="size-4" />
          </Button>

          <Button variant="outline" size="sm" className="ml-1" onClick={() => go({ month: thisMonth() })}>
            Bulan ini
          </Button>

          <div
            role="group"
            aria-label="Tampilan agenda"
            className="ml-1 flex items-center gap-0.5 rounded-sm border border-border p-0.5"
          >
            {views.map((option) => (
              <button
                key={option.value}
                type="button"
                aria-pressed={view === option.value}
                onClick={() => go({ view: option.value })}
                className={cn(
                  'flex cursor-pointer items-center gap-1.5 rounded-xs px-2 py-1 text-xs transition-colors',
                  view === option.value
                    ? 'bg-accent font-medium text-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                )}
              >
                <option.icon aria-hidden className="size-3.5" />
                {option.label}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="border-b border-border px-4 py-1.5">
        <div className="flex flex-wrap items-center gap-2">
          {typeLabels.map((type) => {
            const active = types.includes(type.value);

            return (
              <button
                key={type.value}
                type="button"
                aria-pressed={active}
                onClick={() => toggleType(type.value)}
                className={cn(
                  'flex cursor-pointer items-center gap-1.5 rounded-sm border px-2 py-1 text-xs transition-colors',
                  active
                    ? 'border-border bg-accent text-foreground'
                    : 'border-transparent text-muted-foreground hover:bg-accent/50',
                )}
              >
                <span aria-hidden className={cn('size-2 rounded-full', type.dot)} />
                {type.label}
              </button>
            );
          })}

          <button
            type="button"
            aria-pressed={mine}
            onClick={() => go({ mine: !mine })}
            className={cn(
              'cursor-pointer rounded-sm border px-2 py-1 text-xs transition-colors',
              mine
                ? 'border-border bg-accent text-foreground'
                : 'border-transparent text-muted-foreground hover:bg-accent/50',
            )}
          >
            Hanya milik saya
          </button>

          <span className="ml-auto num text-xs text-muted-foreground">
            {events.length} agenda bulan ini
          </span>
        </div>
      </div>

      <div className="px-4 py-3">
        {view === 'list' ? (
          <AgendaList events={events} />
        ) : (
          <div className="grid items-start gap-3 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <MonthGrid month={month} events={events} selected={selected} onSelect={setSelected} />

            <div className="overflow-hidden rounded-md border border-border bg-card">
              <div className="border-b border-border bg-panel-header px-3 py-2">
                <p className="text-[0.8125rem] font-semibold">{formatLongDate(selected)}</p>
                <p className="num text-xs text-muted-foreground">
                  {selectedEvents.length} agenda pada hari ini
                </p>
              </div>

              {selectedEvents.length === 0 ? (
                <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                  Tidak ada agenda pada tanggal ini.
                </p>
              ) : (
                <ul className="divide-y divide-border/60">
                  {selectedEvents.map((event, index) => (
                    <li key={`${event.url}-${event.kind}-${index}`}>
                      <Link
                        href={event.url}
                        className="flex items-start gap-2.5 px-3 py-2 transition-colors hover:bg-accent"
                      >
                        <span
                          aria-hidden
                          className={cn(
                            'mt-1.5 size-2 shrink-0 rounded-full',
                            kindDots[event.kind] ?? 'bg-muted-foreground',
                          )}
                        />
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-[0.8125rem] font-medium">
                            {event.title}
                          </span>
                          <span className="block truncate text-xs text-muted-foreground">
                            {event.subtitle}
                          </span>
                        </span>
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        )}
      </div>
    </Card>
  );
}