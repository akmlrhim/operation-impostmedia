import { Head, usePage } from '@inertiajs/react';
import { AgendaSection } from '@/components/crm/agenda/agenda-section';
import type { AgendaEvent } from '@/components/crm/agenda/month-grid';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { formatLongDate } from '@/lib/format';
import { dashboard } from '@/routes';

type Props = {
  company: string;
  today: string;
  month: string;
  period: string;
  types: string[];
  mine: boolean;
  view: 'calendar' | 'list';
  events: AgendaEvent[];
};

function greeting(): string {
  const hour = new Date().getHours();

  if (hour < 11) {
    return 'Selamat pagi';
  }

  if (hour < 15) {
    return 'Selamat siang';
  }

  if (hour < 19) {
    return 'Selamat sore';
  }

  return 'Selamat malam';
}

export default function General({ company, today, month, period, types, mine, view, events }: Props) {
  const { auth } = usePage().props;

  return (
    <>
      <Head title="Beranda" />

      <PageHeader
        title={`${greeting()}, ${auth.user?.name}`}
        description={`${company} · ${formatLongDate(today)}`}
      />

      <PageBody>
        <AgendaSection month={month} period={period} types={types} mine={mine} view={view} events={events} />
      </PageBody>
    </>
  );
}

General.layout = {
  breadcrumbs: [{ title: 'Beranda', href: dashboard() }],
};