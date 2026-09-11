import { Link } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, ChevronRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function StatStrip({ children }: { children: ReactNode }) {
  return (
    <div className="flex flex-wrap gap-px overflow-hidden rounded-md border border-border bg-border">
      {children}
    </div>
  );
}

const tones = {
  default: 'text-foreground',
  positive: 'text-emerald-700 dark:text-emerald-400',
  warning: 'text-amber-700 dark:text-amber-400',
  danger: 'text-red-700 dark:text-red-400',
} as const;

export function StatCard({
  label,
  value,
  hint,
  icon: Icon,
  tone = 'default',
  change,
  changeLabel,
  href,
}: {
  label: string;
  value: string;
  hint?: string;
  icon?: LucideIcon;
  tone?: keyof typeof tones;
  change?: number | null;
  changeLabel?: string;
  href?: string;
}) {
  const rising = (change ?? 0) >= 0;
  const ChangeIcon = rising ? ArrowUpRight : ArrowDownRight;

  const body = (
    <>
      <div className="flex items-center gap-1.5">
        {Icon && <Icon aria-hidden className="size-3.5 shrink-0 text-muted-foreground" />}
        <p className="truncate text-[0.6875rem] font-semibold tracking-[0.06em] text-muted-foreground uppercase">
          {label}
        </p>
        {href && (
          <ChevronRight
            aria-hidden
            className="ml-auto size-3.5 shrink-0 text-muted-foreground/60 transition-transform group-hover:translate-x-0.5"
          />
        )}
      </div>

      <p className={cn('num text-xl leading-tight font-semibold', tones[tone])}>{value}</p>

      {change !== null && change !== undefined && (
        <p className="flex items-center gap-1 text-xs">
          <ChangeIcon
            aria-hidden
            className={cn('size-3.5', rising ? tones.positive : tones.danger)}
          />
          <span className={cn('num font-medium', rising ? tones.positive : tones.danger)}>
            {rising ? 'naik' : 'turun'} {Math.abs(change).toLocaleString('id-ID')}%
          </span>
          {changeLabel && <span className="text-muted-foreground">{changeLabel}</span>}
        </p>
      )}

      {hint && <p className="text-xs leading-snug text-muted-foreground">{hint}</p>}
    </>
  );

  const shape = 'flex min-w-56 flex-1 flex-col gap-1 bg-card px-3 py-2.5';

  if (href) {
    return (
      <Link
        href={href}
        prefetch
        className={cn(shape, 'group transition-colors hover:bg-accent/60')}
      >
        {body}
      </Link>
    );
  }

  return <div className={shape}>{body}</div>;
}
