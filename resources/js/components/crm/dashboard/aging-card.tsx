import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { rupiah, rupiahCompact } from '@/lib/format';
import { cn } from '@/lib/utils';

const agingSwatches = ['bg-viz-ramp-1', 'bg-viz-ramp-2', 'bg-viz-ramp-3', 'bg-viz-ramp-4'];

export function AgingCard({ aging }: { aging: { label: string; total: number; count: number }[] }) {
  const agingTotal = aging.reduce((sum, bucket) => sum + bucket.total, 0);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Umur piutang saat ini</CardTitle>
        <p className="text-2xl font-semibold">{rupiah(agingTotal)}</p>
      </CardHeader>

      <CardContent className="space-y-4">
        {agingTotal === 0 ? (
          <p className="text-sm text-muted-foreground">
            Semua invoice sudah lunas. Tidak ada piutang berjalan.
          </p>
        ) : (
          <>
            <div aria-hidden className="flex h-2.5 gap-0.5 overflow-hidden rounded-full">
              {aging.map((bucket, index) =>
                bucket.total > 0 ? (
                  <span
                    key={bucket.label}
                    className={cn(
                      'h-full first:rounded-l-full last:rounded-r-full',
                      agingSwatches[index],
                    )}
                    style={{ width: `${(bucket.total / agingTotal) * 100}%` }}
                  />
                ) : null,
              )}
            </div>

            <ul className="space-y-2.5">
              {aging.map((bucket, index) => (
                <li
                  key={bucket.label}
                  className={cn(
                    'flex items-center gap-2.5 text-sm',
                    bucket.total === 0 && 'text-muted-foreground',
                  )}
                >
                  <span
                    aria-hidden
                    className={cn(
                      'size-2.5 shrink-0 rounded-full',
                      agingSwatches[index],
                      bucket.total === 0 && 'opacity-40',
                    )}
                  />
                  <span className="min-w-0 truncate">{bucket.label}</span>
                  <span className="ml-auto shrink-0 text-xs text-muted-foreground">
                    {bucket.count} inv
                  </span>
                  <span className="shrink-0 font-medium">{rupiahCompact(bucket.total)}</span>
                </li>
              ))}
            </ul>
          </>
        )}
      </CardContent>
    </Card>
  );
}
