import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ClientCombobox } from '@/components/crm/client-combobox';
import { ContractTable } from '@/components/crm/contracts/contract-table';
import { PageHeader } from '@/components/crm/page-header';
import { Pagination } from '@/components/crm/pagination';
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
import type { Client, Contract, Option, Paginated } from '@/types/crm';

type Filters = {
  filterClient: number | null;
  status: string;
  sort: string;
  direction: 'asc' | 'desc';
};

type Props = {
  contracts: Paginated<Contract>;
  filters: Filters;
  statuses: Option[];
  filterClients: Pick<Client, 'id' | 'company_name'>[];
};

export default function ContractsIndex({ contracts, filters, statuses, filterClients }: Props) {
  useRealtime(['contracts'], ['contracts']);

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

      <div className="flex flex-1 flex-col gap-4 p-4">
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

        <ContractTable
          contracts={contracts.data}
          statuses={statuses}
          sort={filters.sort}
          direction={filters.direction}
          onSort={toggleSort}
        />

        <Pagination meta={contracts} />
      </div>
    </>
  );
}

ContractsIndex.layout = {
  breadcrumbs: [{ title: 'MoU & Kontrak', href: index() }],
};
