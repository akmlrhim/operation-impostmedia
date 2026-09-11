import { BarChart3, Table2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { rupiah, rupiahCompact } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { FinanceChartPoint } from '@/types/finance';

const series = [
  { id: 'income', name: 'Debit', swatch: 'bg-viz-1' },
  { id: 'expense', name: 'Kredit', swatch: 'bg-viz-2' },
] as const;

function niceCeil(value: number): number {
  if (value <= 0) {
    return 1;
  }

  const magnitude = 10 ** Math.floor(Math.log10(value));
  const steps = [1, 1.5, 2, 2.5, 3, 4, 5, 7.5, 10];

  return magnitude * (steps.find((step) => value <= step * magnitude) ?? 10);
}

export function FinanceChart({ data, period }: { data: FinanceChartPoint[]; period: string }) {
  const [asTable, setAsTable] = useState(false);
  const [active, setActive] = useState<number | null>(null);

  const ceiling = niceCeil(Math.max(...data.flatMap((point) => [point.income, point.expense]), 0));
  const ticks = [ceiling, ceiling / 2, 0];
  const last = data.length - 1;

  return (
    <Card>
      <CardHeader className="flex-row flex-wrap items-start gap-3">
        <div className="mr-auto space-y-1">
          <CardTitle>Debit vs kredit</CardTitle>
          <p className="text-xs text-muted-foreground">{period}</p>
        </div>

        <Button
          variant="outline"
          size="sm"
          onClick={() => setAsTable((current) => !current)}
          aria-pressed={asTable}
        >
          {asTable ? <BarChart3 /> : <Table2 />}
          {asTable ? 'Grafik' : 'Tabel'}
        </Button>
      </CardHeader>

      <CardContent>
        <div className="mb-3 flex flex-wrap items-center gap-4">
          {series.map((item) => (
            <span key={item.id} className="flex items-center gap-2 text-xs text-muted-foreground">
              <span aria-hidden className={cn('size-2.5 rounded-full', item.swatch)} />
              {item.name}
            </span>
          ))}
        </div>

        {data.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted-foreground">
            Belum ada transaksi pada periode ini.
          </p>
        ) : asTable ? (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-muted-foreground">
                  <th scope="col" className="py-2 text-xs font-semibold tracking-wide uppercase">
                    Periode
                  </th>
                  <th
                    scope="col"
                    className="py-2 text-right text-xs font-semibold tracking-wide uppercase"
                  >
                    Debit
                  </th>
                  <th
                    scope="col"
                    className="py-2 text-right text-xs font-semibold tracking-wide uppercase"
                  >
                    Kredit
                  </th>
                </tr>
              </thead>
              <tbody>
                {data.map((point) => (
                  <tr key={point.key} className="border-b last:border-0">
                    <th scope="row" className="py-2 text-left font-normal">
                      {point.label}
                    </th>
                    <td className="py-1.5 text-right num">{rupiah(point.income)}</td>
                    <td className="py-1.5 text-right num">{rupiah(point.expense)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="flex gap-3">
            <div
              aria-hidden
              className="relative h-48 w-14 shrink-0 num text-xs text-muted-foreground"
            >
              {ticks.map((tick, index) => (
                <span
                  key={tick}
                  className="absolute right-0 -translate-y-1/2"
                  style={{ top: `${(index / (ticks.length - 1)) * 100}%` }}
                >
                  {tick === 0 ? '0' : rupiahCompact(tick)}
                </span>
              ))}
            </div>

            <div className="min-w-0 flex-1">
              <div className="relative h-48">
                <div aria-hidden className="absolute inset-0 flex flex-col justify-between">
                  {ticks.map((tick) => (
                    <div
                      key={tick}
                      className={cn('h-px w-full', tick === 0 ? 'bg-border' : 'bg-border/60')}
                    />
                  ))}
                </div>

                <div className="relative flex h-full items-end gap-1 sm:gap-2">
                  {data.map((point, index) => (
                    <button
                      key={point.key}
                      type="button"
                      className="relative flex h-full flex-1 items-end justify-center gap-0.5 rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                      onMouseEnter={() => setActive(index)}
                      onMouseLeave={() => setActive(null)}
                      onFocus={() => setActive(index)}
                      onBlur={() => setActive(null)}
                      aria-label={`${point.label}: debit ${rupiah(point.income)}, kredit ${rupiah(point.expense)}`}
                    >
                      <span
                        aria-hidden
                        className="viz-bar w-full max-w-8 rounded-t bg-viz-1"
                        style={{
                          height: `${Math.max((point.income / ceiling) * 100, point.income > 0 ? 1.5 : 0)}%`,
                          animationDelay: `${index * 60}ms`,
                        }}
                      />
                      <span
                        aria-hidden
                        className="viz-bar w-full max-w-8 rounded-t bg-viz-2"
                        style={{
                          height: `${Math.max((point.expense / ceiling) * 100, point.expense > 0 ? 1.5 : 0)}%`,
                          animationDelay: `${index * 60 + 30}ms`,
                        }}
                      />

                      {active === index && (
                        <span
                          className={cn(
                            'pointer-events-none absolute bottom-full z-20 mb-2 w-max rounded-md border bg-popover p-2.5 text-left shadow-md',
                            index === 0 && 'left-0',
                            index === last && 'right-0',
                            index !== 0 && index !== last && 'left-1/2 -translate-x-1/2',
                          )}
                        >
                          <span className="block text-xs font-medium">{point.label}</span>
                          {series.map((item) => (
                            <span
                              key={item.id}
                              className="mt-1 flex items-center gap-2 text-xs whitespace-nowrap"
                            >
                              <span
                                aria-hidden
                                className={cn('size-2 rounded-full', item.swatch)}
                              />
                              <span className="text-muted-foreground">{item.name}</span>
                              <span className="ml-auto num font-medium">
                                {rupiah(point[item.id])}
                              </span>
                            </span>
                          ))}
                        </span>
                      )}
                    </button>
                  ))}
                </div>
              </div>

              <div className="mt-2 flex gap-1 sm:gap-2">
                {data.map((point, index) => (
                  <span
                    key={point.key}
                    className={cn(
                      'flex-1 truncate text-center text-xs',
                      index === last ? 'font-medium text-foreground' : 'text-muted-foreground',
                    )}
                  >
                    {point.label}
                  </span>
                ))}
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
