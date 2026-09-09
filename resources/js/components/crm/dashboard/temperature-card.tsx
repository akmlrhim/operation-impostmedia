import { Flame, Snowflake, Thermometer } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { rupiah, rupiahCompact } from '@/lib/format';
import { cn } from '@/lib/utils';

export type TemperatureBucket = {
  value: string;
  label: string;
  description: string;
  count: number;
  total: number;
};

const LOOK: Record<string, { icon: LucideIcon; bar: string; dot: string; text: string }> = {
  hot: {
    icon: Flame,
    bar: 'bg-red-500',
    dot: 'bg-red-500',
    text: 'text-red-600 dark:text-red-400',
  },
  warm: {
    icon: Thermometer,
    bar: 'bg-amber-500',
    dot: 'bg-amber-500',
    text: 'text-amber-600 dark:text-amber-400',
  },
  cold: {
    icon: Snowflake,
    bar: 'bg-sky-500',
    dot: 'bg-sky-500',
    text: 'text-sky-600 dark:text-sky-400',
  },
};

export function TemperatureCard({ temperature }: { temperature: TemperatureBucket[] }) {
  const leads = temperature.reduce((sum, bucket) => sum + bucket.count, 0);
  const pipeline = temperature.reduce((sum, bucket) => sum + bucket.total, 0);

  return (
    <Card>
      <CardHeader className="flex-row flex-wrap items-start gap-3">
        <div className="mr-auto space-y-1">
          <CardTitle className="text-base">Suhu lead saat ini</CardTitle>
          <p className="text-xs text-muted-foreground">
            {leads === 0
              ? 'Belum ada lead terbuka.'
              : `${leads} lead terbuka · ${rupiah(pipeline)} pipeline`}
          </p>
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        <div aria-hidden className="flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-muted">
          {leads > 0 &&
            temperature.map((bucket) =>
              bucket.count > 0 ? (
                <span
                  key={bucket.value}
                  className={cn(
                    'h-full first:rounded-l-full last:rounded-r-full',
                    LOOK[bucket.value]?.bar,
                  )}
                  style={{ width: `${(bucket.count / leads) * 100}%` }}
                />
              ) : null,
            )}
        </div>

        <ul className="grid gap-3 sm:grid-cols-3">
          {temperature.map((bucket) => {
            const look = LOOK[bucket.value];
            const Icon = look?.icon ?? Thermometer;

            return (
              <li
                key={bucket.value}
                className={cn('rounded-lg border p-3', bucket.count === 0 && 'opacity-60')}
              >
                <div className="flex items-center gap-1.5">
                  <Icon aria-hidden className={cn('size-4 shrink-0', look?.text)} />
                  <span className="text-sm font-medium">{bucket.label}</span>
                  <span className="ml-auto text-xs text-muted-foreground">{bucket.count} lead</span>
                </div>

                <p className={cn('mt-1.5 text-lg font-semibold', look?.text)}>
                  {rupiahCompact(bucket.total)}
                </p>

                <p className="mt-0.5 text-xs text-muted-foreground">{bucket.description}</p>
              </li>
            );
          })}
        </ul>
      </CardContent>
    </Card>
  );
}
