import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { Pagination } from '@/components/crm/pagination';
import { Toolbar } from '@/components/crm/toolbar';
import { FinanceFormModal } from '@/components/finance/finance-form-modal';
import { FinanceTable } from '@/components/finance/finance-table';
import { Button } from '@/components/ui/button';
import { DateField } from '@/components/ui/date-field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useRealtime } from '@/hooks/use-realtime';
import { useCan } from '@/lib/use-can';
import { dashboard } from '@/routes';
import { index } from '@/routes/finance';
import type { Option, Paginated } from '@/types/crm';
import type { FinanceSummary, FinanceTransaction } from '@/types/finance';

type Filters = {
  type: string;
  search: string;
  sort: string;
  direction: 'asc' | 'desc';
};

type Props = {
  transactions: Paginated<FinanceTransaction>;
  filters: Filters;
  types: Option[];
  mode: 'month' | 'range';
  month: string;
  months: { value: string; label: string }[];
  range: { from: string; to: string };
  summary: FinanceSummary;
};

export default function FinanceIndex({
  transactions,
  filters,
  types,
  mode,
  month,
  months,
  range,
  summary,
}: Props) {
  useRealtime(['finance-transactions']);

  const [formModal, setFormModal] = useState<{ transaction?: FinanceTransaction } | null>(null);
  const [search, setSearch] = useState(filters.search);
  const can = useCan();

  useEffect(() => {
    if (search === filters.search) {
      return;
    }

    const timer = setTimeout(() => applyFilters({ search }), 400);

    return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search]);

  function go(params: Record<string, string | number | null>) {
    router.get(index().url, params, { preserveState: true, preserveScroll: true, replace: true });
  }

  function applyFilters(next: Partial<Filters>) {
    const merged = { ...filters, ...next };

    go({
      mode,
      month,
      from: range.from,
      to: range.to,
      type: merged.type,
      search: merged.search,
      sort: merged.sort,
      direction: merged.direction,
    });
  }

  function toggleSort(column: string) {
    applyFilters({
      sort: column,
      direction: filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc',
    });
  }

  function changeMode(nextMode: string) {
    go({ mode: nextMode, month, from: range.from, to: range.to, ...filters });
  }

  return (
    <>
      <Head title="Keuangan" />

      <PageHeader
        title="Pemasukan & pengeluaran"
        actions={
          <>
            <Select value={mode} onValueChange={changeMode}>
              <SelectTrigger className="w-32" aria-label="Jenis periode">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="month">Bulanan</SelectItem>
                <SelectItem value="range">Rentang</SelectItem>
              </SelectContent>
            </Select>

            {mode === 'month' ? (
              <Select
                value={month}
                onValueChange={(value) => go({ mode, month: value, ...filters })}
              >
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
                  onChange={(value) => go({ mode, month, from: value, to: range.to, ...filters })}
                  className="w-36"
                />
                <span className="text-muted-foreground">–</span>
                <DateField
                  value={range.to}
                  clearable={false}
                  min={range.from}
                  onChange={(value) => go({ mode, month, from: range.from, to: value, ...filters })}
                  className="w-36"
                />
              </div>
            )}

            {can['manage-finance'] && (
              <Button onClick={() => setFormModal({})}>
                <Plus className="size-4" />
                Transaksi baru
              </Button>
            )}
          </>
        }
      />

      <PageBody>
        <Toolbar trailing={`${transactions.total} transaksi`}>
          <Select
            value={filters.type || 'all'}
            onValueChange={(value) => applyFilters({ type: value === 'all' ? '' : value })}
          >
            <SelectTrigger className="w-full sm:w-48">
              <SelectValue placeholder="Semua jenis" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua jenis</SelectItem>
              {types.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Input
            type="search"
            placeholder="Cari kategori atau catatan"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full sm:w-64"
          />
        </Toolbar>

        <FinanceTable
          transactions={transactions.data}
          summary={summary}
          sort={filters.sort}
          direction={filters.direction}
          onSort={toggleSort}
          onEdit={(transaction) => setFormModal({ transaction })}
        />

        <Pagination meta={transactions} />
      </PageBody>

      {formModal && (
        <FinanceFormModal
          transaction={formModal.transaction}
          types={types}
          onClose={() => setFormModal(null)}
        />
      )}
    </>
  );
}

FinanceIndex.layout = {
  breadcrumbs: [
    { title: 'Beranda', href: dashboard() },
    { title: 'Pemasukan & pengeluaran', href: index() },
  ],
};
