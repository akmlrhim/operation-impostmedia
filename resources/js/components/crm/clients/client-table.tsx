import { Link, router } from '@inertiajs/react';
import { FileSignature, Pencil, Receipt, Trash2 } from 'lucide-react';
import { BulkActionsBar } from '@/components/crm/bulk-actions-bar';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { DocumentMenu } from '@/components/crm/document-menu';
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
import { formatDate, rupiah } from '@/lib/format';
import { useCan } from '@/lib/use-can';
import { destroy, destroyBulk, exportMethod as exportClients, show } from '@/routes/clients';
import { create as createContract, show as showContract } from '@/routes/contracts';
import { create as createInvoice, show as showInvoice } from '@/routes/invoices';
import type { Client, Option } from '@/types/crm';

export function ClientTable({
  clients,
  statuses,
  sort,
  direction,
  onSort,
  onEdit,
}: {
  clients: Client[];
  statuses: Option[];
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
  onEdit: (client: Client) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(clients);
  const can = useCan();

  return (
    <div className="flex flex-col gap-3">
      {can['manage-records'] && (
        <BulkActionsBar
          count={selection.count}
          noun="klien"
          exportHref={exportClients({ query: { ids: Array.from(selection.selected) } }).url}
          deleteTitle={`Hapus ${selection.count} klien?`}
          deleteDescription="MoU dan invoice klien ini tetap tersimpan, tapi jadi tanpa klien."
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
        <Table className="min-w-3xl">
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">
                <Checkbox
                  checked={
                    selection.allSelected ? true : selection.someSelected ? 'indeterminate' : false
                  }
                  onCheckedChange={() => selection.toggleAll()}
                  aria-label="Pilih semua klien"
                />
              </TableHead>
              <SortableTableHead
                label="Perusahaan"
                active={sort === 'company_name'}
                direction={direction}
                onClick={() => onSort('company_name')}
              />
              <SortableTableHead
                label="PIC"
                active={sort === 'contact_name'}
                direction={direction}
                onClick={() => onSort('contact_name')}
              />
              <TableHead className="text-center">MoU</TableHead>
              <TableHead className="text-center">Invoice</TableHead>
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
            {clients.length === 0 && (
              <TableRow>
                <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                  Belum ada klien yang cocok.
                </TableCell>
              </TableRow>
            )}

            {clients.map((client) => (
              <TableRow key={client.id}>
                <TableCell>
                  <Checkbox
                    checked={selection.selected.has(client.id)}
                    onCheckedChange={() => selection.toggle(client.id)}
                    aria-label={`Pilih ${client.company_name}`}
                  />
                </TableCell>
                <TableCell>
                  <Link href={show(client.id)} className="font-medium hover:underline">
                    {client.company_name}
                  </Link>
                  {client.city && <p className="text-xs text-muted-foreground">{client.city}</p>}
                </TableCell>
                <TableCell>
                  {client.contact_name ?? '-'}
                  {client.contact_position && (
                    <p className="text-xs text-muted-foreground">{client.contact_position}</p>
                  )}
                </TableCell>
                <TableCell className="text-center">
                  <DocumentMenu
                    icon={FileSignature}
                    ariaLabel={`MoU ${client.company_name}`}
                    emptyLabel="Belum ada MoU."
                    createHref={createContract({ query: { client: client.id } })}
                    createLabel="Buat MoU baru"
                    count={client.contracts_count}
                    entries={(client.contracts ?? []).map((contract) => ({
                      id: contract.id,
                      href: showContract(contract.id),
                      primary: contract.number,
                      secondary: contract.title,
                      amount: rupiah(contract.value),
                      status: contract.status,
                    }))}
                  />
                </TableCell>
                <TableCell className="text-center">
                  <DocumentMenu
                    icon={Receipt}
                    ariaLabel={`Invoice ${client.company_name}`}
                    emptyLabel="Belum ada invoice."
                    createHref={createInvoice({ query: { client: client.id } })}
                    createLabel="Buat invoice baru"
                    count={client.invoices_count}
                    entries={(client.invoices ?? []).map((invoice) => ({
                      id: invoice.id,
                      href: showInvoice(invoice.id),
                      primary: invoice.number,
                      secondary: `Jatuh tempo ${formatDate(invoice.due_date)}`,
                      amount: rupiah(invoice.total),
                      status: invoice.status,
                    }))}
                  />
                </TableCell>
                <TableCell>
                  <StatusBadge value={client.status} options={statuses} />
                </TableCell>
                <TableCell>
                  <RowActions
                    label={client.company_name}
                    actions={[
                      { label: 'Ubah klien', icon: Pencil, onSelect: () => onEdit(client) },
                      {
                        label: 'Buat MoU',
                        icon: FileSignature,
                        href: createContract({ query: { client: client.id } }),
                      },
                      {
                        label: 'Buat invoice',
                        icon: Receipt,
                        href: createInvoice({ query: { client: client.id } }),
                      },
                      ...(can['manage-records']
                        ? [
                            {
                              label: 'Hapus klien',
                              icon: Trash2,
                              destructive: true,
                              onSelect: async () => {
                                const confirmed = await confirm({
                                  title: `Hapus klien ${client.company_name}?`,
                                  description:
                                    'MoU dan invoice-nya tetap tersimpan, tapi jadi tanpa klien.',
                                  confirmLabel: 'Hapus klien',
                                  destructive: true,
                                });

                                if (confirmed) {
                                  router.delete(destroy(client.id), { preserveScroll: true });
                                }
                              },
                            },
                          ]
                        : []),
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
