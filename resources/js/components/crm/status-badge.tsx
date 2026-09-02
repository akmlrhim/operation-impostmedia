import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const tones: Record<string, string> = {
  open: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
  won: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
  lost: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
  draft: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  review: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  signed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
  active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
  completed: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
  expired: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
  terminated: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
  cancelled: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',

  sent: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
  partially_paid: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
  paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
  overdue: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
  void: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',

  inactive: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  churned: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',

  low: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  medium: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
  high: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
  urgent: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

export function StatusBadge({
  value,
  options,
  className,
}: {
  value: string;
  options?: { value: string; label: string }[];
  className?: string;
}) {
  const label = options?.find((o) => o.value === value)?.label ?? value;

  return (
    <Badge variant="secondary" className={cn('border-transparent', tones[value], className)}>
      {label}
    </Badge>
  );
}
