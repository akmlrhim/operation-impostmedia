import type { ReactNode } from 'react';
import { decimal, rupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { LineItem } from '@/types/crm';

const COLUMNS = 'md:grid-cols-[minmax(0,1fr)_7rem_9rem_9rem]';

function Cell({
  label,
  strong,
  children,
}: {
  label: string;
  strong?: boolean;
  children: ReactNode;
}) {
  return (
    <div className="flex items-baseline justify-between gap-3 md:block md:text-right">
      <span className="text-xs font-semibold tracking-wide text-muted-foreground uppercase md:hidden">
        {label}
      </span>
      <span className={strong ? 'font-medium' : undefined}>{children}</span>
    </div>
  );
}

export function DocumentItems({ items }: { items: LineItem[] }) {
  if (items.length === 0) {
    return (
      <p className="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
        Belum ada item.
      </p>
    );
  }

  return (
    <div className="rounded-lg border">
      <div
        className={cn(
          'hidden gap-4 border-b bg-muted/60 px-4 py-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase md:grid',
          COLUMNS,
        )}
      >
        <div>Uraian</div>
        <div className="text-right">Volume</div>
        <div className="text-right">Harga Satuan</div>
        <div className="text-right">Jumlah</div>
      </div>

      {items.map((item, index) => (
        <div
          key={item.id ?? index}
          className={cn('grid gap-2 border-b px-4 py-3 text-sm last:border-b-0 md:gap-4', COLUMNS)}
        >
          <div>
            <p className="font-medium">{item.name}</p>
            {item.description && (
              <p className="mt-0.5 text-xs whitespace-pre-line text-muted-foreground">
                {item.description}
              </p>
            )}
          </div>

          <Cell label="Volume">
            {decimal(item.quantity)} {item.unit}
          </Cell>
          <Cell label="Harga Satuan">{rupiah(item.unit_price)}</Cell>
          <Cell label="Jumlah" strong>
            {rupiah(item.amount)}
          </Cell>
        </div>
      ))}
    </div>
  );
}
