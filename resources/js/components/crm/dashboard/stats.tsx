import { CircleDollarSign, Receipt, Target, Wallet } from 'lucide-react';
import { StatCard } from '@/components/crm/stat-card';
import { rupiahCompact } from '@/lib/format';

export function DashboardStats({
  stats,
  period,
}: {
  period: string;
  stats: {
    collected: number;
    collectedChange: number | null;
    issued: number;
    issuedChange: number | null;
    outstanding: number;
    overdue: number;
    overdueCount: number;
    pipeline: number;
    openLeads: number;
  };
}) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard
        label={`Uang masuk ${period}`}
        value={rupiahCompact(stats.collected)}
        icon={CircleDollarSign}
        tone="positive"
        change={stats.collectedChange}
        changeLabel="dari bulan sebelumnya"
      />
      <StatCard
        label={`Tagihan terbit ${period}`}
        value={rupiahCompact(stats.issued)}
        icon={Receipt}
        change={stats.issuedChange}
        changeLabel="dari bulan sebelumnya"
      />
      <StatCard
        label="Piutang berjalan saat ini"
        value={rupiahCompact(stats.outstanding)}
        icon={Wallet}
        tone={stats.overdueCount > 0 ? 'danger' : 'default'}
        hint={
          stats.overdueCount > 0
            ? `${rupiahCompact(stats.overdue)} dari ${stats.overdueCount} invoice sudah lewat jatuh tempo`
            : 'Tidak ada tunggakan'
        }
      />
      <StatCard
        label="Nilai pipeline saat ini"
        value={rupiahCompact(stats.pipeline)}
        icon={Target}
        hint={`${stats.openLeads} lead masih terbuka`}
      />
    </div>
  );
}
