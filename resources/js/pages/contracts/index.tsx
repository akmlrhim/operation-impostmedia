import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ClientCombobox } from '@/components/crm/client-combobox';
import { ContractTable } from '@/components/crm/contracts/contract-table';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { Pagination } from '@/components/crm/pagination';
import { Toolbar } from '@/components/crm/toolbar';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useRealtime } from '@/hooks/use-realtime';
import { create, index } from '@/routes/contracts';
import type { Client, ContractGroup, Option, Paginated } from '@/types/crm';

type Filters = {
  filterClient: number | null;
  status: string;
  sort: string;
  direction: 'asc' | 'desc';
};

type Props = {
  groups: Paginated<ContractGroup>;
  orphans: ContractGroup | null;
  filters: Filters;
  statuses: Option[];
  filterClients: Pick<Client, 'id' | 'company_name'>[];
};

export default function ContractsIndex({
  groups,
  orphans,
  filters,
  statuses,
  filterClients,
}: Props) {
  useRealtime(['contracts'], ['groups', 'orphans']);

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
      <Head title="MoU & Kontrak" />

      <PageHeader
        title="MoU & Kontrak"
        actions={
          <Button asChild>
            <Link href={create()}>
              <Plus className="size-4" />
              Buat MoU
            </Link>
          </Button>
        }
      />

      <PageBody>
        <Toolbar trailing={`${groups.total} klien`}>
          <ClientCombobox
            clients={filterClients}
            value={filters.filterClient}
            onChange={(value) => applyFilters({ filterClient: value })}
          />

          <Select
            value={filters.status || 'all'}
            onValueChange={(value) => applyFilters({ status: value === 'all' ? '' : value })}
          >
            <SelectTrigger className="w-full sm:w-48">
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
        </Toolbar>

        <ContractTable
          groups={orphans ? [orphans, ...groups.data] : groups.data}
          statuses={statuses}
          sort={filters.sort}
          direction={filters.direction}
          onSort={toggleSort}
        />

        <Pagination meta={groups} />
      </PageBody>
    </>
  );
}

ContractsIndex.layout = {
  breadcrumbs: [{ title: 'MoU & Kontrak', href: index() }],
};
