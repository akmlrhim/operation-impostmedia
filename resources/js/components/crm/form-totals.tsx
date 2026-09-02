import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function FormTotals({ children }: { children: ReactNode }) {
  return <div className="ml-auto w-full space-y-4 sm:max-w-sm">{children}</div>;
}

export function TotalsRow({
  label,
  strong,
  children,
}: {
  label: string;
  strong?: boolean;
  children: ReactNode;
}) {
  return (
    <div
      className={cn(
        'flex items-baseline justify-between gap-4',
        strong && 'border-t pt-2 text-base font-semibold',
      )}
    >
      <dt className={strong ? undefined : 'text-muted-foreground'}>{label}</dt>
      <dd>{children}</dd>
    </div>
  );
}
