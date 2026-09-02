import { ArrowDownRight, ArrowUpRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function StatCard({
  label,
  value,
  hint,
  icon: Icon,
  tone = 'default',
  change,
  changeLabel,
}: {
  label: string;
  value: string;
  hint?: string;
  icon?: LucideIcon;
  tone?: 'default' | 'positive' | 'warning' | 'danger';
  change?: number | null;
  changeLabel?: string;
}) {
  const tones = {
    default: 'text-foreground',
    positive: 'text-emerald-600 dark:text-emerald-400',
    warning: 'text-amber-600 dark:text-amber-400',
    danger: 'text-red-600 dark:text-red-400',
  } as const;

  const rising = (change ?? 0) >= 0;
  const ChangeIcon = rising ? ArrowUpRight : ArrowDownRight;

  return (
    <Card className="gap-0 py-4">
      <CardContent className="px-4">
        <div className="flex items-center justify-between gap-2">
          <p className="text-sm text-muted-foreground">{label}</p>
          {Icon && <Icon aria-hidden className="size-4 text-muted-foreground" />}
        </div>
        <p className={cn('mt-1.5 text-2xl font-semibold', tones[tone])}>{value}</p>

        {change !== null && change !== undefined && (
          <p className="mt-1 flex items-center gap-1 text-xs">
            <ChangeIcon
              aria-hidden
              className={cn('size-3.5', rising ? tones.positive : tones.danger)}
            />
            <span className={cn('font-medium', rising ? tones.positive : tones.danger)}>
              {rising ? 'naik' : 'turun'} {Math.abs(change).toLocaleString('id-ID')}%
            </span>
            {changeLabel && <span className="text-muted-foreground">{changeLabel}</span>}
          </p>
        )}

        {hint && <p className="mt-0.5 text-xs text-muted-foreground">{hint}</p>}
      </CardContent>
    </Card>
  );
}
