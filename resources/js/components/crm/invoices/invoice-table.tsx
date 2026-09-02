import { Link, router } from '@inertiajs/react';
import { Pencil, Trash2, Wallet } from 'lucide-react';
import { BulkActionsBar } from '@/components/crm/bulk-actions-bar';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { RowActions } from '@/components/crm/row-actions';
import { SortableTableHead } from '@/components/crm/sortable-table-head';
import { StatusBadge } from '@/components/crm/status-badge';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useRowSelection } from '@/hooks/use-row-selection';
import { formatDate, relativeDueLabel, rupiah } from '@/lib/format';
import { show as showClient } from '@/routes/clients';
import {
  destroy,
  destroyBulk,
  edit,
  exportMethod as exportInvoices,
  show,
} from '@/routes/invoices';
import type { Invoice, Option } from '@/types/crm';

export function InvoiceTable({
  invoices,
  statuses,
  sort,
  direction,
  onSort,
}: {
  invoices: Invoice[];
  statuses: Option[];
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(invoices);

  return (
    <div className="flex flex-col gap-3">
      <BulkActionsBar
        count={selection.count}
        noun="invoice"
        exportHref={exportInvoices({ query: { ids: Array.from(selection.selected) } }).url}
        deleteTitle={`Hapus ${selection.count} invoice?`}
        deleteDescription="Invoice yang sudah ada pembayarannya akan dilewati."
        onDelete={() => {
          router.delete(destroyBulk().url, {
            data: { ids: Array.from(selection.selected) },
            preserveScroll: true,
            onSuccess: selection.clear,
          });
        }}
        onClear={selection.clear}
      />

      <Card className="overflow-hidden rounded-sm py-0">
        <Table className="min-w-3xl">
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">
                <Checkbox
                  checked={
                    selection.allSelected ? true : selection.someSelected ? 'indeterminate' : false
                  }
                  onCheckedChange={() => selection.toggleAll()}
                  aria-label="Pilih semua invoice"
                />
              </TableHead>
              <SortableTableHead
                label="Nomor"
                active={sort === 'number'}
                direction={direction}
                onClick={() => onSort('number')}
              />
              <SortableTableHead
                label="Klien"
                active={sort === 'client'}
                direction={direction}
                onClick={() => onSort('client')}
              />
              <SortableTableHead
                label="Terbit"
                active={sort === 'issue_date'}
                direction={direction}
                onClick={() => onSort('issue_date')}
              />
              <SortableTableHead
                label="Jatuh tempo"
                active={sort === 'due_date'}
                direction={direction}
                onClick={() => onSort('due_date')}
              />
              <SortableTableHead
                label="Total"
                active={sort === 'total'}
                direction={direction}
                onClick={() => onSort('total')}
                className="text-right"
              />
              <SortableTableHead
                label="Sisa"
                active={sort === 'balance_due'}
                direction={direction}
                onClick={() => onSort('balance_due')}
                className="text-right"
              />
              <SortableTableHead
                label="Status"
                active={sort === 'status'}
                direction={direction}
                onClick={() => onSort('status')}
              />
              <TableHead className="w-12" />
            </TableRow>
          </TableHeader>
          <TableBody>
            {invoices.length === 0 && (
              <TableRow>
                <TableCell colSpan={9} className="py-10 text-center text-muted-foreground">
                  Belum ada invoice yang cocok.
                </TableCell>
              </TableRow>
            )}

            {invoices.map((invoice) => (
              <TableRow key={invoice.id}>
                <TableCell>
                  <Checkbox
                    checked={selection.selected.has(invoice.id)}
                    onCheckedChange={() => selection.toggle(invoice.id)}
                    aria-label={`Pilih ${invoice.number}`}
                  />
                </TableCell>
                <TableCell>
                  <Link href={show(invoice.id)} className="text-xs font-medium hover:underline">
                    {invoice.number}
                  </Link>
                </TableCell>
                <TableCell>
                  {invoice.client ? (
                    <Link href={showClient(invoice.client.id)} className="hover:underline">
                      {invoice.client.company_name}
                    </Link>
                  ) : (
                    '-'
                  )}
                </TableCell>
                <TableCell className="text-xs text-muted-foreground">
                  {formatDate(invoice.issue_date)}
                </TableCell>
                <TableCell className="text-xs">
                  {formatDate(invoice.due_date)}
                  {['sent', 'partially_paid', 'overdue'].includes(invoice.status) && (
                    <span className="block text-muted-foreground">
                      {relativeDueLabel(invoice.due_date)}
                    </span>
                  )}
                </TableCell>
                <TableCell className="text-right">{rupiah(invoice.total)}</TableCell>
                <TableCell className="text-right font-medium">
                  {rupiah(invoice.balance_due)}
                </TableCell>
                <TableCell>
                  <StatusBadge value={invoice.status} options={statuses} />
                </TableCell>
                <TableCell>
                  <RowActions
                    label={invoice.number}
                    actions={[
                      {
                        label: 'Ubah invoice',
                        icon: Pencil,
                        href: edit(invoice.id),
                        disabledReason:
                          invoice.status === 'draft' ? undefined : 'Sudah dikirim ke klien.',
                      },
                      {
                        label: 'Catat pembayaran',
                        icon: Wallet,
                        href: show(invoice.id, { query: { pay: 1 } }),
                        disabledReason:
                          invoice.status === 'draft'
                            ? 'Invoice masih draf.'
                            : Number(invoice.balance_due) <= 0
                              ? 'Tidak ada sisa tagihan.'
                              : undefined,
                      },
                      {
                        label: 'Hapus invoice',
                        icon: Trash2,
                        destructive: true,
                        disabledReason:
                          (invoice.payments_count ?? 0) > 0 ? 'Sudah ada pembayaran.' : undefined,
                        onSelect: async () => {
                          const confirmed = await confirm({
                            title: `Hapus invoice ${invoice.number}?`,
                            description: 'Nomor invoice yang sudah terpakai tidak dipakai ulang.',
                            confirmLabel: 'Hapus invoice',
                            destructive: true,
                          });

                          if (confirmed) {
                            router.delete(destroy(invoice.id), { preserveScroll: true });
                          }
                        },
                      },
                    ]}
                  />
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>

        {confirmDialog}
      </Card>
    </div>
  );
}
