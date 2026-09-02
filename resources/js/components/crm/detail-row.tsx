import type { ReactNode } from 'react';

export function DetailRow({
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
      className={
        strong
          ? 'flex items-start justify-between gap-3 border-t pt-1.5 font-semibold'
          : 'flex items-start justify-between gap-3'
      }
    >
      <dt className={strong ? 'shrink-0' : 'shrink-0 text-muted-foreground'}>{label}</dt>
      <dd className="text-right">{children}</dd>
    </div>
  );
}
