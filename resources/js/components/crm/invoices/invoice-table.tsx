import { Link, router } from '@inertiajs/react';
import { BadgeCheck, Pencil, Send, Trash2, Wallet } from 'lucide-react';
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
  send,
  settle,
  show,
} from '@/routes/invoices';
import type { Invoice, InvoiceGroup, Option } from '@/types/crm';

const OUTSTANDING = ['sent', 'partially_paid', 'overdue'];

export function InvoiceTable({
  groups,
  statuses,
  sort,
  direction,
  onSort,
}: {
  groups: InvoiceGroup[];
  statuses: Option[];
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(groups.flatMap((group) => group.invoices));

  function toggleGroup(invoices: Invoice[]) {
    const allSelected = invoices.every((invoice) => selection.selected.has(invoice.id));

    for (const invoice of invoices) {
      if (allSelected || !selection.selected.has(invoice.id)) {
        selection.toggle(invoice.id);
      }
    }
  }

  return (
    <div className="flex flex-col gap-3">
      <BulkActionsBar
        count={selection.count}
        noun="invoice"
        exportHref={exportInvoices({ query: { ids: Array.from(selection.selected) } }).url}
        deleteTitle={`Hapus ${selection.count} invoice?`}
        deleteDescription="Pembayaran yang tercatat ikut terhapus bersama invoice-nya."
        onDelete={() => {
          router.delete(destroyBulk().url, {
            data: { ids: Array.from(selection.selected) },
            preserveScroll: true,
            onSuccess: selection.clear,
          });
        }}
        onClear={selection.clear}
      />

      {groups.length === 0 && (
        <Card className="rounded-sm py-10 text-center text-muted-foreground">
          Belum ada invoice yang cocok.
        </Card>
      )}

      {groups.map((group) => {
        const groupSelected =
          group.invoices.length > 0 &&
          group.invoices.every((invoice) => selection.selected.has(invoice.id));
        const groupPartial =
          !groupSelected && group.invoices.some((invoice) => selection.selected.has(invoice.id));

        return (
          <Card key={group.id ?? 'orphans'} className="gap-0 overflow-hidden rounded-sm py-0">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/40 px-4 py-3">
              {group.id === null ? (
                <span className="font-medium text-muted-foreground">{group.company_name}</span>
              ) : (
                <Link href={showClient(group.id)} className="font-medium hover:underline">
                  {group.company_name}
                </Link>
              )}
              <p className="text-sm text-muted-foreground">
                {group.invoices_count} invoice · {rupiah(group.invoices_total)} · sisa{' '}
                {rupiah(group.invoices_balance_due)}
              </p>
            </div>

            <Table className="min-w-3xl">
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10">
                    <Checkbox
                      checked={groupSelected ? true : groupPartial ? 'indeterminate' : false}
                      onCheckedChange={() => toggleGroup(group.invoices)}
                      aria-label={`Pilih semua invoice ${group.company_name}`}
                    />
                  </TableHead>
                  <SortableTableHead
                    label="Nomor"
                    active={sort === 'number'}
                    direction={direction}
                    onClick={() => onSort('number')}
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
                {group.invoices.map((invoice) => (
                  <TableRow key={invoice.id}>
                    <TableCell>
                      <Checkbox
                        checked={selection.selected.has(invoice.id)}
                        onCheckedChange={() => selection.toggle(invoice.id)}
                        aria-label={`Pilih ${invoice.number}`}
                      />
                    </TableCell>
                    <TableCell>
                      <Link href={show(invoice.id)} className="font-medium hover:underline">
                        {invoice.number}
                      </Link>
                    </TableCell>
                    <TableCell>{formatDate(invoice.issue_date)}</TableCell>
                    <TableCell>
                      {formatDate(invoice.due_date)}
                      {['sent', 'partially_paid', 'overdue'].includes(invoice.status) && (
                        <span className="block">{relativeDueLabel(invoice.due_date)}</span>
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
                            label: 'Tandai terkirim',
                            icon: Send,
                            disabledReason:
                              invoice.status === 'draft' ? undefined : 'Sudah lewat tahap draf.',
                            onSelect: async () => {
                              const confirmed = await confirm({
                                title: `Tandai ${invoice.number} sudah dikirim?`,
                                description:
                                  'Invoice mulai dihitung sebagai piutang dan PDF-nya diarsipkan.',
                                confirmLabel: 'Tandai terkirim',
                              });

                              if (confirmed) {
                                router.post(send(invoice.id), {}, { preserveScroll: true });
                              }
                            },
                          },
                          {
                            label: 'Lunaskan',
                            icon: BadgeCheck,
                            disabledReason: !OUTSTANDING.includes(invoice.status)
                              ? 'Invoice belum terkirim.'
                              : Number(invoice.balance_due) <= 0
                                ? 'Tidak ada sisa tagihan.'
                                : undefined,
                            onSelect: async () => {
                              const confirmed = await confirm({
                                title: `Lunaskan ${invoice.number}?`,
                                description: `Sisa ${rupiah(invoice.balance_due)} dicatat sebagai pembayaran transfer hari ini.`,
                                confirmLabel: 'Lunaskan',
                              });

                              if (confirmed) {
                                router.post(settle(invoice.id), {}, { preserveScroll: true });
                              }
                            },
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
                            onSelect: async () => {
                              const confirmed = await confirm({
                                title: `Hapus invoice ${invoice.number}?`,
                                description:
                                  'Nomor invoice yang sudah terpakai tidak dipakai ulang.',
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
          </Card>
        );
      })}

      {confirmDialog}
    </div>
  );
}
