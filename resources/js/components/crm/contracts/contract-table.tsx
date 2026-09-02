import { Link, router } from '@inertiajs/react';
import { Pencil, Receipt, Trash2 } from 'lucide-react';
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
import { formatDate, rupiah } from '@/lib/format';
import { show as showClient } from '@/routes/clients';
import {
  destroy,
  destroyBulk,
  edit,
  exportMethod as exportContracts,
  invoice as createInvoiceFrom,
  show,
} from '@/routes/contracts';
import type { Contract, Option } from '@/types/crm';

const INVOICEABLE = ['signed', 'active', 'completed'];

export function ContractTable({
  contracts,
  statuses,
  sort,
  direction,
  onSort,
}: {
  contracts: Contract[];
  statuses: Option[];
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(contracts);

  return (
    <div className="flex flex-col gap-3">
      <BulkActionsBar
        count={selection.count}
        noun="MoU"
        exportHref={exportContracts({ query: { ids: Array.from(selection.selected) } }).url}
        deleteTitle={`Hapus ${selection.count} MoU?`}
        deleteDescription="MoU yang sudah punya invoice akan dilewati, sisanya beserta ruang lingkup dan lampirannya ikut terhapus."
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
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-10">
                <Checkbox
                  checked={
                    selection.allSelected ? true : selection.someSelected ? 'indeterminate' : false
                  }
                  onCheckedChange={() => selection.toggleAll()}
                  aria-label="Pilih semua MoU"
                />
              </TableHead>
              <SortableTableHead
                label="Nomor"
                active={sort === 'number'}
                direction={direction}
                onClick={() => onSort('number')}
              />
              <SortableTableHead
                label="Judul"
                active={sort === 'title'}
                direction={direction}
                onClick={() => onSort('title')}
              />
              <SortableTableHead
                label="Klien"
                active={sort === 'client'}
                direction={direction}
                onClick={() => onSort('client')}
              />
              <SortableTableHead
                label="Periode"
                active={sort === 'start_date'}
                direction={direction}
                onClick={() => onSort('start_date')}
              />
              <SortableTableHead
                label="Nilai"
                active={sort === 'value'}
                direction={direction}
                onClick={() => onSort('value')}
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
            {contracts.length === 0 && (
              <TableRow>
                <TableCell colSpan={8} className="py-10 text-center text-muted-foreground">
                  Belum ada MoU yang cocok.
                </TableCell>
              </TableRow>
            )}

            {contracts.map((contract) => (
              <TableRow key={contract.id}>
                <TableCell>
                  <Checkbox
                    checked={selection.selected.has(contract.id)}
                    onCheckedChange={() => selection.toggle(contract.id)}
                    aria-label={`Pilih ${contract.number}`}
                  />
                </TableCell>
                <TableCell>
                  <Link href={show(contract.id)} className="text-xs font-medium hover:underline">
                    {contract.number}
                  </Link>
                </TableCell>
                <TableCell className="max-w-xs truncate">{contract.title}</TableCell>
                <TableCell>
                  {contract.client ? (
                    <Link href={showClient(contract.client.id)} className="hover:underline">
                      {contract.client.company_name}
                    </Link>
                  ) : (
                    '-'
                  )}
                </TableCell>
                <TableCell className="text-xs text-muted-foreground">
                  {formatDate(contract.start_date)} – {formatDate(contract.end_date)}
                </TableCell>
                <TableCell className="text-right font-medium">{rupiah(contract.value)}</TableCell>
                <TableCell>
                  <StatusBadge value={contract.status} options={statuses} />
                </TableCell>
                <TableCell>
                  <RowActions
                    label={contract.number}
                    actions={[
                      {
                        label: 'Ubah MoU',
                        icon: Pencil,
                        href: edit(contract.id),
                      },
                      {
                        label: 'Terbitkan invoice',
                        icon: Receipt,
                        disabledReason: INVOICEABLE.includes(contract.status)
                          ? undefined
                          : 'MoU belum ditandatangani.',
                        onSelect: () =>
                          router.post(createInvoiceFrom(contract.id), {}, { preserveScroll: true }),
                      },
                      {
                        label: 'Hapus MoU',
                        icon: Trash2,
                        destructive: true,
                        disabledReason:
                          (contract.invoices_count ?? 0) > 0 ? 'Sudah punya invoice.' : undefined,
                        onSelect: async () => {
                          const confirmed = await confirm({
                            title: `Hapus MoU ${contract.number}?`,
                            description: 'Ruang lingkup pekerjaan dan lampirannya ikut terhapus.',
                            confirmLabel: 'Hapus MoU',
                            destructive: true,
                          });

                          if (confirmed) {
                            router.delete(destroy(contract.id), { preserveScroll: true });
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
