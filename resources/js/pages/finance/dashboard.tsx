import { Head, router } from '@inertiajs/react';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { FinanceChart } from '@/components/finance/finance-chart';
import { FinanceSummaryCards } from '@/components/finance/finance-summary';
import { DateField } from '@/components/ui/date-field';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useRealtime } from '@/hooks/use-realtime';
import { dashboard } from '@/routes';
import { dashboard as financeDashboard } from '@/routes/finance';
import type { FinanceChartPoint, FinanceSummary } from '@/types/finance';

type Props = {
  mode: 'month' | 'range';
  month: string;
  months: { value: string; label: string }[];
  range: { from: string; to: string };
  period: string;
  summary: FinanceSummary;
  chart: FinanceChartPoint[];
};

export default function FinanceDashboard({ mode, month, months, range, period, summary, chart }: Props) {
  useRealtime(['finance-transactions']);

  function go(params: Record<string, string | number | null>) {
    router.get(financeDashboard().url, params, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  return (
    <>
      <Head title="Ringkasan" />

      <PageHeader
        title="Ringkasan keuangan"
        actions={
          <>
            <Select value={mode} onValueChange={(value) => go({ mode: value, month, ...range })}>
              <SelectTrigger className="w-32" aria-label="Jenis periode">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="month">Bulanan</SelectItem>
                <SelectItem value="range">Rentang</SelectItem>
              </SelectContent>
            </Select>

            {mode === 'month' ? (
              <Select value={month} onValueChange={(value) => go({ mode, month: value, ...range })}>
                <SelectTrigger className="w-40" aria-label="Pilih bulan">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {months.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            ) : (
              <div className="flex items-center gap-1.5">
                <DateField
                  value={range.from}
                  clearable={false}
                  max={range.to}
                  onChange={(value) => go({ mode, month, from: value, to: range.to })}
                  className="w-36"
                />
                <span className="text-muted-foreground">–</span>
                <DateField
                  value={range.to}
                  clearable={false}
                  min={range.from}
                  onChange={(value) => go({ mode, month, from: range.from, to: value })}
                  className="w-36"
                />
              </div>
            )}
          </>
        }
      />

      <PageBody>
        <FinanceSummaryCards summary={summary} period={period} />

        <FinanceChart data={chart} period={period} />
      </PageBody>
    </>
  );
}

FinanceDashboard.layout = {
  breadcrumbs: [
    { title: 'Beranda', href: dashboard() },
    { title: 'Ringkasan', href: financeDashboard() },
  ],
};
