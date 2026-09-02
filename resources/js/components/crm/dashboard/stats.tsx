import { CircleDollarSign, Receipt, Target, Wallet } from 'lucide-react';
import { StatCard } from '@/components/crm/stat-card';
import { rupiahCompact } from '@/lib/format';

export function DashboardStats({
  stats,
}: {
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
        label="Uang masuk bulan ini"
        value={rupiahCompact(stats.collected)}
        icon={CircleDollarSign}
        tone="positive"
        change={stats.collectedChange}
        changeLabel="dari bulan lalu"
      />
      <StatCard
        label="Tagihan terbit bulan ini"
        value={rupiahCompact(stats.issued)}
        icon={Receipt}
        change={stats.issuedChange}
        changeLabel="dari bulan lalu"
      />
      <StatCard
        label="Piutang berjalan"
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
        label="Nilai pipeline"
        value={rupiahCompact(stats.pipeline)}
        icon={Target}
        hint={`${stats.openLeads} lead masih terbuka`}
      />
    </div>
  );
}
