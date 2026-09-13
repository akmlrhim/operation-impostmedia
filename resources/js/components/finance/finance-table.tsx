import { router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { BulkActionsBar } from '@/components/crm/bulk-actions-bar';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { RowActions } from '@/components/crm/row-actions';
import { SortableTableHead } from '@/components/crm/sortable-table-head';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Table,
  TableBody,
  TableCell,
  TableFooter,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useRowSelection } from '@/hooks/use-row-selection';
import { formatDate, rupiah } from '@/lib/format';
import { useCan } from '@/lib/use-can';
import { destroy, destroyBulk, exportMethod } from '@/routes/finance';
import type { FinanceSummary, FinanceTransaction } from '@/types/finance';

export function FinanceTable({
  transactions,
  summary,
  sort,
  direction,
  onSort,
  onEdit,
}: {
  transactions: FinanceTransaction[];
  summary: FinanceSummary;
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
  onEdit: (transaction: FinanceTransaction) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(transactions);
  const can = useCan();

  return (
    <div className="flex flex-col gap-3">
      {(can['manage-finance'] || can['export-finance']) && (
        <BulkActionsBar
          count={selection.count}
          noun="transaksi"
          exportHref={exportMethod({ query: { ids: Array.from(selection.selected) } }).url}
          deleteTitle={`Hapus ${selection.count} transaksi?`}
          deleteDescription="Transaksi yang dihapus tidak bisa dikembalikan."
          onDelete={() => {
            router.delete(destroyBulk().url, {
              data: { ids: Array.from(selection.selected) },
              preserveScroll: true,
              onSuccess: selection.clear,
            });
          }}
          onClear={selection.clear}
        />
      )}

      <Card className="overflow-hidden py-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">
                <Checkbox
                  checked={
                    selection.allSelected ? true : selection.someSelected ? 'indeterminate' : false
                  }
                  onCheckedChange={() => selection.toggleAll()}
                  aria-label="Pilih semua transaksi"
                />
              </TableHead>
              <SortableTableHead
                label="Tanggal"
                active={sort === 'transaction_date'}
                direction={direction}
                onClick={() => onSort('transaction_date')}
              />
              <SortableTableHead
                label="Keterangan"
                active={sort === 'category'}
                direction={direction}
                onClick={() => onSort('category')}
              />
              <TableHead className="text-right">Debit</TableHead>
              <TableHead className="text-right">Kredit</TableHead>
              <TableHead className="w-12" />
            </TableRow>
          </TableHeader>
          <TableBody>
            {transactions.length === 0 && (
              <TableRow>
                <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                  Belum ada transaksi pada periode ini.
                </TableCell>
              </TableRow>
            )}

            {transactions.map((transaction) => {
              const isDebit = transaction.type === 'income';

              return (
                <TableRow key={transaction.id}>
                  <TableCell>
                    <Checkbox
                      checked={selection.selected.has(transaction.id)}
                      onCheckedChange={() => selection.toggle(transaction.id)}
                      aria-label={`Pilih ${transaction.category}`}
                    />
                  </TableCell>
                  <TableCell className="whitespace-nowrap">
                    {formatDate(transaction.transaction_date)}
                  </TableCell>
                  <TableCell>
                    <span className="font-medium">{transaction.category}</span>
                    {transaction.notes && (
                      <p className="text-xs text-muted-foreground">{transaction.notes}</p>
                    )}
                  </TableCell>
                  <TableCell className="text-right num font-medium text-emerald-700 dark:text-emerald-400">
                    {isDebit ? rupiah(transaction.amount) : '-'}
                  </TableCell>
                  <TableCell className="text-right num font-medium text-red-700 dark:text-red-400">
                    {isDebit ? '-' : rupiah(transaction.amount)}
                  </TableCell>
                  <TableCell>
                    <RowActions
                      label={transaction.category}
                      actions={[
                        ...(can['manage-finance']
                          ? [
                              {
                                label: 'Ubah transaksi',
                                icon: Pencil,
                                onSelect: () => onEdit(transaction),
                              },
                              {
                                label: 'Hapus transaksi',
                                icon: Trash2,
                                destructive: true,
                                onSelect: async () => {
                                  const confirmed = await confirm({
                                    title: `Hapus transaksi ${transaction.category}?`,
                                    description: 'Transaksi yang dihapus tidak bisa dikembalikan.',
                                    confirmLabel: 'Hapus transaksi',
                                    destructive: true,
                                  });

                                  if (confirmed) {
                                    router.delete(destroy(transaction.id), {
                                      preserveScroll: true,
                                    });
                                  }
                                },
                              },
                            ]
                          : []),
                      ]}
                    />
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>

          <TableFooter>
            <TableRow>
              <TableCell colSpan={3} className="text-xs tracking-wide uppercase">
                Total periode ini
              </TableCell>
              <TableCell className="text-right num text-emerald-700 dark:text-emerald-400">
                {rupiah(summary.income)}
              </TableCell>
              <TableCell className="text-right num text-red-700 dark:text-red-400">
                {rupiah(summary.expense)}
              </TableCell>
              <TableCell />
            </TableRow>
            <TableRow>
              <TableCell colSpan={3} className="text-xs tracking-wide uppercase">
                Saldo
              </TableCell>
              <TableCell colSpan={2} className="text-right num">
                {rupiah(summary.net)}
              </TableCell>
              <TableCell />
            </TableRow>
          </TableFooter>
        </Table>

        {confirmDialog}
      </Card>
    </div>
  );
}
