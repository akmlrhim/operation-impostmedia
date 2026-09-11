import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { Popover, PopoverAnchor, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const MONTHS = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
];

const MONTHS_SHORT = MONTHS.map((month) => month.slice(0, 3));

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

function pad(value: number): string {
  return String(value).padStart(2, '0');
}

function toIso(date: Date): string {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function fromIso(iso: string): Date | null {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);

  if (!match) {
    return null;
  }

  const [, year, month, day] = match;
  const date = new Date(Number(year), Number(month) - 1, Number(day));

  return date.getMonth() === Number(month) - 1 && date.getDate() === Number(day) ? date : null;
}

function isoToTyped(iso: string): string {
  const date = fromIso(iso);

  return date ? `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}` : '';
}

function maskTyped(raw: string): string {
  const digits = raw.replace(/\D/g, '').slice(0, 8);

  return [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)]
    .filter((part) => part !== '')
    .join('/');
}

function typedToIso(text: string): string | null {
  const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(text);

  if (!match) {
    return null;
  }

  const [, day, month, year] = match;

  return fromIso(`${year}-${month}-${day}`) ? `${year}-${month}-${day}` : null;
}

function addDays(date: Date, amount: number): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate() + amount);
}

function addMonths(date: Date, amount: number): Date {
  return new Date(date.getFullYear(), date.getMonth() + amount, 1);
}

function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

function outOfRange(iso: string, min?: string, max?: string): boolean {
  return Boolean((min && iso < min) || (max && iso > max));
}

function preventFocusSteal(event: React.MouseEvent): void {
  event.preventDefault();
}

function monthGrid(view: Date): Date[] {
  const first = startOfMonth(view);
  const offset = (first.getDay() + 6) % 7;
  const start = addDays(first, -offset);

  return Array.from({ length: 42 }, (_, index) => addDays(start, index));
}

const cellBase =
  'flex size-8 cursor-pointer items-center justify-center rounded-md text-sm transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-40';

const navBase =
  'flex size-7 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50';

const shellBase =
  'flex h-8 w-full min-w-0 items-center gap-1 rounded-md border border-input bg-background pr-1 pl-2.5 shadow-xs transition-[color,box-shadow] focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50';

export type DateFieldProps = {
  id?: string;
  value: string;
  onChange: (value: string) => void;
  min?: string;
  max?: string;
  required?: boolean;
  disabled?: boolean;
  invalid?: boolean;
  clearable?: boolean;
  className?: string;
};

export function DateField({
  id,
  value,
  onChange,
  min,
  max,
  required,
  disabled,
  invalid,
  clearable = true,
  className,
}: DateFieldProps) {
  const [text, setText] = useState(() => isoToTyped(value));
  const [open, setOpen] = useState(false);
  const [panel, setPanel] = useState<'days' | 'months' | 'years'>('days');
  const [view, setView] = useState(() => startOfMonth(fromIso(value) ?? new Date()));
  const [focused, setFocused] = useState(() => value || toIso(new Date()));
  const [grabFocus, setGrabFocus] = useState(false);

  const shellRef = useRef<HTMLDivElement>(null);
  const gridRef = useRef<HTMLDivElement>(null);
  const todayIso = useMemo(() => toIso(new Date()), []);
  const days = useMemo(() => monthGrid(view), [view]);

  useEffect(() => {
    setText(isoToTyped(value));
  }, [value]);

  useEffect(() => {
    if (!open) {
      return;
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key !== 'Escape') {
        return;
      }

      event.preventDefault();
      setOpen(false);
      setGrabFocus(false);
    }

    window.addEventListener('keydown', handleEscape, true);

    return () => window.removeEventListener('keydown', handleEscape, true);
  }, [open]);

  useEffect(() => {
    if (!open || panel !== 'days' || !grabFocus) {
      return;
    }

    gridRef.current?.querySelector<HTMLButtonElement>(`[data-iso="${focused}"]`)?.focus();
  }, [focused, grabFocus, open, panel]);

  const typedIso = typedToIso(text);
  const malformed = text !== '' && (typedIso === null || outOfRange(typedIso, min, max));

  function commit(iso: string) {
    if (outOfRange(iso, min, max)) {
      return;
    }

    onChange(iso);
    setView(startOfMonth(fromIso(iso) ?? new Date()));
    setFocused(iso);
  }

  function handleType(raw: string) {
    const masked = maskTyped(raw);
    setText(masked);

    if (masked === '') {
      onChange('');

      return;
    }

    const iso = typedToIso(masked);

    if (iso !== null && !outOfRange(iso, min, max)) {
      commit(iso);
    }
  }

  function openAt(withFocus: boolean) {
    const anchor = fromIso(value) ?? fromIso(min ?? '') ?? new Date();

    setPanel('days');
    setView(startOfMonth(anchor));
    setFocused(value || toIso(anchor));
    setGrabFocus(withFocus);
    setOpen(true);
  }

  function handleGridKeys(event: React.KeyboardEvent<HTMLDivElement>) {
    const current = fromIso(focused);

    if (current === null) {
      return;
    }

    const steps: Record<string, number> = {
      ArrowLeft: -1,
      ArrowRight: 1,
      ArrowUp: -7,
      ArrowDown: 7,
    };

    let next: Date | null = null;

    if (event.key in steps) {
      next = addDays(current, steps[event.key]);
    } else if (event.key === 'PageUp') {
      next = new Date(current.getFullYear(), current.getMonth() - 1, current.getDate());
    } else if (event.key === 'PageDown') {
      next = new Date(current.getFullYear(), current.getMonth() + 1, current.getDate());
    } else if (event.key === 'Home') {
      next = addDays(current, -((current.getDay() + 6) % 7));
    } else if (event.key === 'End') {
      next = addDays(current, 6 - ((current.getDay() + 6) % 7));
    }

    if (next === null) {
      return;
    }

    event.preventDefault();
    setFocused(toIso(next));
    setView(startOfMonth(next));
  }

  const title =
    panel === 'years'
      ? `${Math.floor(view.getFullYear() / 12) * 12} – ${Math.floor(view.getFullYear() / 12) * 12 + 11}`
      : panel === 'months'
        ? String(view.getFullYear())
        : `${MONTHS[view.getMonth()]} ${view.getFullYear()}`;

  function step(direction: number) {
    if (panel === 'days') {
      setView(addMonths(view, direction));
    } else if (panel === 'months') {
      setView(new Date(view.getFullYear() + direction, view.getMonth(), 1));
    } else {
      setView(new Date(view.getFullYear() + direction * 12, view.getMonth(), 1));
    }
  }

  return (
    <Popover
      open={open}
      onOpenChange={(next) => {
        if (next) {
          openAt(true);

          return;
        }

        setOpen(false);
        setGrabFocus(false);
      }}
    >
      <PopoverAnchor asChild>
        <div
          ref={shellRef}
          data-slot="date-field"
          aria-disabled={disabled}
          className={cn(
            shellBase,
            (invalid || malformed) &&
              'border-destructive ring-destructive/20 dark:ring-destructive/40',
            disabled && 'pointer-events-none opacity-50',
            className,
          )}
        >
          <input
            id={id}
            type="text"
            inputMode="numeric"
            autoComplete="off"
            placeholder="dd/mm/yyyy"
            value={text}
            required={required}
            disabled={disabled}
            aria-invalid={invalid || malformed}
            onChange={(event) => handleType(event.target.value)}
            onClick={() => !open && openAt(false)}
            onKeyDown={(event) => {
              if (event.key === 'ArrowDown') {
                event.preventDefault();
                openAt(true);
              }
            }}
            onBlur={() => setText(isoToTyped(value))}
            className="w-full min-w-0 bg-transparent text-base outline-none placeholder:text-muted-foreground md:text-sm"
          />

          <PopoverTrigger
            type="button"
            disabled={disabled}
            aria-label="Buka kalender"
            className="flex size-7 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
          >
            <CalendarIcon className="size-4" />
          </PopoverTrigger>
        </div>
      </PopoverAnchor>

      <PopoverContent
        className="w-auto p-3"
        onOpenAutoFocus={(event) => {
          if (!grabFocus) {
            event.preventDefault();
          }
        }}
        onInteractOutside={(event) => {
          if (shellRef.current?.contains(event.target as Node)) {
            event.preventDefault();
          }
        }}
        onFocusOutside={(event) => {
          if (shellRef.current?.contains(event.target as Node)) {
            event.preventDefault();
          }
        }}
      >
        <div className="flex items-center justify-between gap-2 pb-2">
          <button
            type="button"
            onMouseDown={preventFocusSteal}
            onClick={() => step(-1)}
            className={navBase}
            aria-label="Sebelumnya"
          >
            <ChevronLeftIcon className="size-4" />
          </button>

          <button
            type="button"
            onMouseDown={preventFocusSteal}
            onClick={() => setPanel(panel === 'days' ? 'months' : panel === 'months' ? 'years' : 'days')}
            className="cursor-pointer rounded-md px-2 py-1 text-sm font-medium transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50"
          >
            {title}
          </button>

          <button
            type="button"
            onMouseDown={preventFocusSteal}
            onClick={() => step(1)}
            className={navBase}
            aria-label="Berikutnya"
          >
            <ChevronRightIcon className="size-4" />
          </button>
        </div>

        {panel === 'days' && (
          <>
            <div className="grid grid-cols-7 gap-0.5 pb-1">
              {WEEKDAYS.map((day, index) => (
                <div
                  key={day}
                  aria-hidden
                  className={cn(
                    'flex size-8 items-center justify-center text-[0.7rem] font-medium',
                    index > 4 ? 'text-muted-foreground/70' : 'text-muted-foreground',
                  )}
                >
                  {day}
                </div>
              ))}
            </div>

            <div
              ref={gridRef}
              role="grid"
              onKeyDown={handleGridKeys}
              className="grid grid-cols-7 gap-0.5"
            >
              {days.map((day) => {
                const iso = toIso(day);
                const outside = day.getMonth() !== view.getMonth();
                const selected = iso === value;
                const disabledDay = outOfRange(iso, min, max);

                return (
                  <button
                    key={iso}
                    type="button"
                    role="gridcell"
                    data-iso={iso}
                    disabled={disabledDay}
                    tabIndex={iso === focused ? 0 : -1}
                    aria-selected={selected}
                    aria-label={`${day.getDate()} ${MONTHS[day.getMonth()]} ${day.getFullYear()}`}
                    onMouseDown={preventFocusSteal}
                    onClick={() => {
                      commit(iso);
                      setOpen(false);
                    }}
                    className={cn(
                      cellBase,
                      'hover:bg-accent hover:text-accent-foreground',
                      outside && 'text-muted-foreground/50',
                      iso === todayIso && !selected && 'font-semibold ring-1 ring-ring',
                      selected &&
                        'bg-primary font-medium text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                    )}
                  >
                    {day.getDate()}
                  </button>
                );
              })}
            </div>
          </>
        )}

        {panel === 'months' && (
          <div className="grid grid-cols-3 gap-1">
            {MONTHS_SHORT.map((month, index) => (
              <button
                key={month}
                type="button"
                onMouseDown={preventFocusSteal}
                onClick={() => {
                  setView(new Date(view.getFullYear(), index, 1));
                  setPanel('days');
                }}
                className={cn(
                  'cursor-pointer rounded-md px-2 py-2 text-sm transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50',
                  index === view.getMonth() && 'bg-primary text-primary-foreground hover:bg-primary',
                )}
              >
                {month}
              </button>
            ))}
          </div>
        )}

        {panel === 'years' && (
          <div className="grid grid-cols-3 gap-1">
            {Array.from({ length: 12 }, (_, index) => Math.floor(view.getFullYear() / 12) * 12 + index).map(
              (year) => (
                <button
                  key={year}
                  type="button"
                  onMouseDown={preventFocusSteal}
                  onClick={() => {
                    setView(new Date(year, view.getMonth(), 1));
                    setPanel('months');
                  }}
                  className={cn(
                    'cursor-pointer rounded-md px-2 py-2 text-sm transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    year === view.getFullYear() &&
                      'bg-primary text-primary-foreground hover:bg-primary',
                  )}
                >
                  {year}
                </button>
              ),
            )}
          </div>
        )}

        <div className="mt-2 flex items-center justify-between gap-2 border-t pt-2">
          <button
            type="button"
            disabled={outOfRange(todayIso, min, max)}
            onMouseDown={preventFocusSteal}
            onClick={() => {
              commit(todayIso);
              setOpen(false);
            }}
            className="cursor-pointer rounded-md px-2 py-1 text-xs font-medium transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-40"
          >
            Hari ini
          </button>

          {clearable && value !== '' && (
            <button
              type="button"
              onMouseDown={preventFocusSteal}
              onClick={() => {
                onChange('');
                setOpen(false);
              }}
              className="cursor-pointer rounded-md px-2 py-1 text-xs text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
              Kosongkan
            </button>
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}

export function DateTimeField({
  id,
  value,
  onChange,
  defaultTime = '09:00',
  ...rest
}: Omit<DateFieldProps, 'clearable'> & { defaultTime?: string }) {
  const [datePart = '', timePart = ''] = value.split('T');
  const time = timePart.slice(0, 5);

  return (
    <div className="flex gap-2">
      <DateField
        {...rest}
        id={id}
        value={datePart}
        onChange={(next) => onChange(next === '' ? '' : `${next}T${time || defaultTime}`)}
      />

      <label
        className={cn(
          shellBase,
          'w-28 shrink-0 pr-3',
          rest.disabled && 'pointer-events-none opacity-50',
        )}
      >
        <span className="sr-only">Jam</span>
        <input
          type="time"
          value={time}
          disabled={rest.disabled}
          onChange={(event) =>
            onChange(datePart === '' ? '' : `${datePart}T${event.target.value || defaultTime}`)
          }
          onClick={(event) => {
            try {
              event.currentTarget.showPicker();
            } catch {
            }
          }}
          className="w-full min-w-0 bg-transparent text-base outline-none md:text-sm [&::-webkit-calendar-picker-indicator]:opacity-60"
        />
      </label>
    </div>
  );
}
