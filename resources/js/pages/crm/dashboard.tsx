import { Head, router } from '@inertiajs/react';
import { ActivitiesCard } from '@/components/crm/dashboard/activities-card';
import { AgingCard } from '@/components/crm/dashboard/aging-card';
import { AttentionCard } from '@/components/crm/dashboard/attention-card';
import { DashboardStats } from '@/components/crm/dashboard/stats';
import { TemperatureCard } from '@/components/crm/dashboard/temperature-card';
import type { TemperatureBucket } from '@/components/crm/dashboard/temperature-card';
import { TrendChart } from '@/components/crm/dashboard/trend-chart';
import type { TrendPoint } from '@/components/crm/dashboard/trend-chart';
import type { AttentionItem } from '@/components/crm/dashboard/types';
import { PageHeader } from '@/components/crm/page-header';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useRealtime } from '@/hooks/use-realtime';
import { dashboard } from '@/routes';
import { dashboard as crmDashboard } from '@/routes/crm';

type Props = {
  company: string;
  month: string;
  months: { value: string; label: string }[];
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
  trend: TrendPoint[];
  temperature: TemperatureBucket[];
  aging: { label: string; total: number; count: number }[];
  attention: AttentionItem[];
  activities: {
    id: number;
    type: string;
    title: string;
    subject: string | null;
    user: string | null;
    at: string | null;
  }[];
};

export default function CrmDashboard({
  month,
  months,
  period,
  stats,
  trend,
  temperature,
  aging,
  attention,
  activities,
}: Props) {
  useRealtime([
    'leads',
    'lead-stages',
    'clients',
    'contracts',
    'invoices',
    'services',
    'activities',
    'attachments',
  ]);

  return (
    <>
      <Head title="Dashboard CRM" />

      <div className="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
          title="Dashboard CRM"
          actions={
            <Select
              value={month}
              onValueChange={(value) =>
                router.get(
                  crmDashboard().url,
                  { month: value },
                  { preserveState: true, preserveScroll: true, replace: true },
                )
              }
            >
              <SelectTrigger className="w-44" aria-label="Periode dashboard">
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
          }
        />

        <DashboardStats stats={stats} period={period} />

        <TemperatureCard temperature={temperature} />

        <div className="grid gap-4 lg:grid-cols-3">
          <TrendChart data={trend} period={period} />
          <AgingCard aging={aging} />
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <AttentionCard attention={attention} />
          <ActivitiesCard activities={activities} period={period} />
        </div>
      </div>
    </>
  );
}

CrmDashboard.layout = {
  breadcrumbs: [
    { title: 'General', href: dashboard() },
    { title: 'Dashboard CRM', href: crmDashboard() },
  ],
};
