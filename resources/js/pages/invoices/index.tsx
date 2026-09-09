import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, FileEdit, Plus, Receipt } from 'lucide-react';
import { ClientCombobox } from '@/components/crm/client-combobox';
import { InvoiceTable } from '@/components/crm/invoices/invoice-table';
import { PageHeader } from '@/components/crm/page-header';
import { Pagination } from '@/components/crm/pagination';
import { StatCard } from '@/components/crm/stat-card';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useRealtime } from '@/hooks/use-realtime';
import { rupiahCompact } from '@/lib/format';
import { create, index } from '@/routes/invoices';
import type { Client, InvoiceGroup, Option, Paginated } from '@/types/crm';

type Filters = {
  filterClient: number | null;
  status: string;
  sort: string;
  direction: 'asc' | 'desc';
};

type Props = {
  groups: Paginated<InvoiceGroup>;
  orphans: InvoiceGroup | null;
  filters: Filters;
  statuses: Option[];
  summary: { outstanding: number; overdue: number; draft: number };
  filterClients: Pick<Client, 'id' | 'company_name'>[];
};

export default function InvoicesIndex({
  groups,
  orphans,
  filters,
  statuses,
  summary,
  filterClients,
}: Props) {
  useRealtime(['invoices'], ['groups', 'orphans', 'summary']);

  function applyFilters(next: Partial<Filters>) {
    const merged = { ...filters, ...next };

    router.get(
      index().url,
      {
        filter_client: merged.filterClient,
        status: merged.status,
        sort: merged.sort,
        direction: merged.direction,
      },
      { preserveState: true, replace: true },
    );
  }

  function toggleSort(column: string) {
    applyFilters({
      sort: column,
      direction: filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc',
    });
  }

  return (
    <>
      <Head title="Invoice" />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title="Invoice"
          actions={
            <Button asChild>
              <Link href={create()}>
                <Plus className="size-4" />
                Buat invoice
              </Link>
            </Button>
          }
        />

        <div className="grid gap-4 sm:grid-cols-3">
          <StatCard
            label="Piutang berjalan"
            value={rupiahCompact(summary.outstanding)}
            icon={Receipt}
          />
          <StatCard
            label="Lewat jatuh tempo"
            value={rupiahCompact(summary.overdue)}
            icon={AlertTriangle}
            tone={summary.overdue > 0 ? 'danger' : 'default'}
          />
          <StatCard label="Masih draf" value={String(summary.draft)} icon={FileEdit} />
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <ClientCombobox
            clients={filterClients}
            value={filters.filterClient}
            onChange={(value) => applyFilters({ filterClient: value })}
          />

          <Select
            value={filters.status || 'all'}
            onValueChange={(value) => applyFilters({ status: value === 'all' ? '' : value })}
          >
            <SelectTrigger className="w-full sm:w-52">
              <SelectValue placeholder="Semua status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua status</SelectItem>
              {statuses.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <InvoiceTable
          groups={orphans ? [orphans, ...groups.data] : groups.data}
          statuses={statuses}
          sort={filters.sort}
          direction={filters.direction}
          onSort={toggleSort}
        />

        <Pagination meta={groups} />
      </div>
    </>
  );
}

InvoicesIndex.layout = {
  breadcrumbs: [{ title: 'Invoice', href: index() }],
};
