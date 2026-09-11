import { Link } from '@inertiajs/react';
import { kindDots, todayKey } from '@/components/crm/agenda/month-grid';
import type { AgendaEvent } from '@/components/crm/agenda/month-grid';
import { formatLongDate, relativeDueLabel } from '@/lib/format';
import { cn } from '@/lib/utils';

function groupByDay(events: AgendaEvent[]): { date: string; events: AgendaEvent[] }[] {
  const days: { date: string; events: AgendaEvent[] }[] = [];

  for (const event of events) {
    const last = days.at(-1);

    if (last && last.date === event.date) {
      last.events.push(event);
    } else {
      days.push({ date: event.date, events: [event] });
    }
  }

  return days;
}

export function AgendaList({ events }: { events: AgendaEvent[] }) {
  const days = groupByDay(events);
  const today = todayKey();

  if (days.length === 0) {
    return (
      <div className="rounded-md border border-border bg-card px-3 py-10 text-center text-sm text-muted-foreground">
        Tidak ada agenda pada bulan ini.
      </div>
    );
  }

  return (
    <div className="rounded-md border border-border bg-card">
      {days.map((day, i) => (
        <section key={day.date} className={cn(i > 0 && 'mt-3 border-t border-border/60')}>
          <div className="flex flex-wrap items-center justify-between gap-x-3 border-b border-border bg-panel-header px-3 py-1.5">
            <h2
              className={cn(
                'text-[0.8125rem]',
                day.date === today ? 'font-semibold' : 'font-medium',
              )}
            >
              {formatLongDate(day.date)}
            </h2>

            <p className="flex items-center gap-1.5 num text-xs text-muted-foreground">
              {day.date === today ? (
                <span className="rounded-full bg-primary px-1.5 py-0.5 text-[0.6875rem] font-semibold text-primary-foreground">
                  Hari ini
                </span>
              ) : (
                <span>{relativeDueLabel(day.date)}</span>
              )}
              <span>· {day.events.length} agenda</span>
            </p>
          </div>

          <ul className="divide-y divide-border/60">
            {day.events.map((event, index) => (
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

                  <span className="min-w-0 flex-1 sm:flex sm:items-baseline sm:gap-3">
                    <span className="block truncate text-[0.8125rem] font-medium sm:w-64 sm:shrink-0">
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
        </section>
      ))}
    </div>
  );
}
