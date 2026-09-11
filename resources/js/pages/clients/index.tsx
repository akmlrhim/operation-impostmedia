import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { ClientCombobox } from '@/components/crm/client-combobox';
import { ClientFormModal } from '@/components/crm/client-form-modal';
import { ClientTable } from '@/components/crm/clients/client-table';
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
import { index } from '@/routes/clients';
import type { Client, Option, Paginated, UserOption } from '@/types/crm';

type Filters = {
  status: string;
  filterClient: number | null;
  sort: string;
  direction: 'asc' | 'desc';
};

type Props = {
  clients: Paginated<Client>;
  filters: Filters;
  statuses: Option[];
  users: UserOption[];
  filterClients: Pick<Client, 'id' | 'company_name'>[];
};

export default function ClientsIndex({ clients, filters, statuses, users, filterClients }: Props) {
  useRealtime(['clients', 'contracts', 'invoices'], ['clients']);

  const [clientModal, setClientModal] = useState<{ client?: Client } | null>(null);

  function applyFilters(next: Partial<Filters>) {
    const merged = { ...filters, ...next };

    router.get(
      index().url,
      {
        status: merged.status,
        filter_client: merged.filterClient,
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
      <Head title="Klien" />

      <PageHeader
        title="Klien"
        actions={
          <Button onClick={() => setClientModal({})}>
            <Plus className="size-4" />
            Klien baru
          </Button>
        }
      />

      <PageBody>
        <Toolbar trailing={`${clients.total} klien`}>
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

        <ClientTable
          clients={clients.data}
          statuses={statuses}
          sort={filters.sort}
          direction={filters.direction}
          onSort={toggleSort}
          onEdit={(client) => setClientModal({ client })}
        />

        <Pagination meta={clients} />
      </PageBody>

      {clientModal && (
        <ClientFormModal
          client={clientModal.client}
          statuses={statuses}
          users={users}
          onClose={() => setClientModal(null)}
        />
      )}
    </>
  );
}

ClientsIndex.layout = {
  breadcrumbs: [{ title: 'Klien', href: index() }],
};
