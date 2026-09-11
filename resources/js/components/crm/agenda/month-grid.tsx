import { cn } from '@/lib/utils';

export type AgendaEvent = {
  date: string;
  kind: string;
  title: string;
  subtitle: string;
  url: string;
};

export const kindDots: Record<string, string> = {
  lead: 'bg-sky-500',
  invoice: 'bg-amber-500',
  'contract-start': 'bg-emerald-500',
  'contract-end': 'bg-violet-500',
  activity: 'bg-slate-400',
};

const weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

function pad(value: number): string {
  return value < 10 ? `0${value}` : String(value);
}

export function dayKey(year: number, month: number, day: number): string {
  return `${year}-${pad(month)}-${pad(day)}`;
}

export function todayKey(): string {
  const now = new Date();

  return dayKey(now.getFullYear(), now.getMonth() + 1, now.getDate());
}

export function MonthGrid({
  month,
  events,
  selected,
  onSelect,
}: {
  month: string;
  events: AgendaEvent[];
  selected: string;
  onSelect: (day: string) => void;
}) {
  const [year, monthNumber] = month.split('-').map(Number);
  const firstWeekday = (new Date(year, monthNumber - 1, 1).getDay() + 6) % 7;
  const dayCount = new Date(year, monthNumber, 0).getDate();
  const cellCount = Math.ceil((firstWeekday + dayCount) / 7) * 7;
  const today = todayKey();

  const byDay = new Map<string, AgendaEvent[]>();

  for (const event of events) {
    const bucket = byDay.get(event.date);

    if (bucket) {
      bucket.push(event);
    } else {
      byDay.set(event.date, [event]);
    }
  }

  return (
    <div className="overflow-hidden rounded-md border border-border bg-card">
      <div className="grid grid-cols-7 border-b border-border bg-panel-header">
        {weekdays.map((label) => (
          <div
            key={label}
            className="px-2 py-1.5 text-center text-[0.6875rem] font-semibold tracking-[0.06em] text-muted-foreground uppercase"
          >
            {label}
          </div>
        ))}
      </div>

      <div className="grid grid-cols-7">
        {Array.from({ length: cellCount }, (_, index) => {
          const dayNumber = index - firstWeekday + 1;
          const inMonth = dayNumber >= 1 && dayNumber <= dayCount;

          if (!inMonth) {
            return (
              <div
                key={index}
                aria-hidden
                className="min-h-24 border-r border-b border-border/60 bg-muted/40 last:border-r-0"
              />
            );
          }

          const key = dayKey(year, monthNumber, dayNumber);
          const dayEvents = byDay.get(key) ?? [];
          const isToday = key === today;
          const isSelected = key === selected;

          return (
            <button
              key={index}
              type="button"
              onClick={() => onSelect(key)}
              aria-pressed={isSelected}
              className={cn(
                'min-h-24 cursor-pointer border-r border-b border-border/60 p-1.5 text-left align-top transition-colors [&:nth-child(7n)]:border-r-0',
                isSelected ? 'bg-accent' : 'hover:bg-accent/50',
              )}
            >
              <span
                className={cn(
                  'inline-flex size-5 items-center justify-center rounded-full num text-xs',
                  isToday
                    ? 'bg-primary font-semibold text-primary-foreground'
                    : 'text-muted-foreground',
                )}
              >
                {dayNumber}
              </span>

              <span className="mt-1 block space-y-0.5">
                {dayEvents.slice(0, 2).map((event, position) => (
                  <span
                    key={`${event.url}-${event.kind}-${position}`}
                    className="flex items-center gap-1"
                  >
                    <span
                      aria-hidden
                      className={cn(
                        'size-1.5 shrink-0 rounded-full',
                        kindDots[event.kind] ?? 'bg-muted-foreground',
                      )}
                    />
                    <span className="truncate text-[0.6875rem]">{event.title}</span>
                  </span>
                ))}

                {dayEvents.length > 2 && (
                  <span className="block num text-[0.6875rem] text-muted-foreground">
                    +{dayEvents.length - 2} lagi
                  </span>
                )}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
