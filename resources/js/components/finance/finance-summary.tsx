import { ArrowDownCircle, ArrowUpCircle, Scale } from 'lucide-react';
import { StatCard, StatStrip } from '@/components/crm/stat-card';
import { rupiahCompact } from '@/lib/format';
import type { FinanceSummary } from '@/types/finance';

export function FinanceSummaryCards({
  summary,
  period,
}: {
  summary: FinanceSummary;
  period: string;
}) {
  return (
    <StatStrip>
      <StatCard
        label={`Debit ${period}`}
        value={rupiahCompact(summary.income)}
        icon={ArrowUpCircle}
        tone="positive"
        hint="Total pemasukan"
      />
      <StatCard
        label={`Kredit ${period}`}
        value={rupiahCompact(summary.expense)}
        icon={ArrowDownCircle}
        tone="danger"
        hint="Total pengeluaran"
      />
      <StatCard
        label={`Saldo ${period}`}
        value={rupiahCompact(summary.net)}
        icon={Scale}
        tone={summary.net >= 0 ? 'positive' : 'danger'}
        hint={summary.net >= 0 ? 'Debit lebih besar dari kredit' : 'Kredit melebihi debit'}
      />
    </StatStrip>
  );
}
