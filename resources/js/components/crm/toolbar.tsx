import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function Toolbar({
  children,
  trailing,
  className,
}: {
  children: ReactNode;
  trailing?: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        'flex flex-wrap items-center gap-2 rounded-md border border-border bg-card px-2 py-1.5',
        className,
      )}
    >
      {children}
      {trailing && (
        <div className="ml-auto flex shrink-0 items-center gap-2 text-xs text-muted-foreground">
          {trailing}
        </div>
      )}
    </div>
  );
}
