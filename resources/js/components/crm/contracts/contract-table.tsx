import { Link, router } from '@inertiajs/react';
import { Pencil, PenLine, Trash2 } from 'lucide-react';
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
import { useCan } from '@/lib/use-can';
import { show as showClient } from '@/routes/clients';
import {
  destroy,
  destroyBulk,
  edit,
  exportMethod as exportContracts,
  show,
  sign,
} from '@/routes/contracts';
import type { Contract, ContractGroup, Option } from '@/types/crm';

const SIGNABLE = ['draft', 'review', 'sent'];

export function ContractTable({
  groups,
  statuses,
  sort,
  direction,
  onSort,
}: {
  groups: ContractGroup[];
  statuses: Option[];
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(groups.flatMap((group) => group.contracts));
  const can = useCan();

  function toggleGroup(contracts: Contract[]) {
    const allSelected = contracts.every((contract) => selection.selected.has(contract.id));

    for (const contract of contracts) {
      if (allSelected || !selection.selected.has(contract.id)) {
        selection.toggle(contract.id);
      }
    }
  }

  return (
    <div className="flex flex-col gap-3">
      {can['manage-records'] && (
        <BulkActionsBar
          count={selection.count}
          noun="MoU"
          exportHref={exportContracts({ query: { ids: Array.from(selection.selected) } }).url}
          deleteTitle={`Hapus ${selection.count} MoU?`}
          deleteDescription="Ruang lingkup dan lampirannya ikut terhapus. Invoice yang sudah terbit tetap tersimpan."
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

      {groups.length === 0 && (
        <Card className="py-8 text-center text-sm text-muted-foreground">
          Belum ada MoU yang cocok.
        </Card>
      )}

      {groups.map((group) => {
        const groupSelected =
          group.contracts.length > 0 &&
          group.contracts.every((contract) => selection.selected.has(contract.id));
        const groupPartial =
          !groupSelected && group.contracts.some((contract) => selection.selected.has(contract.id));

        return (
          <Card key={group.id ?? 'orphans'} className="gap-0 overflow-hidden py-0">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/40 px-4 py-3">
              {group.id === null ? (
                <span className="font-medium text-muted-foreground">{group.company_name}</span>
              ) : (
                <Link href={showClient(group.id)} className="font-medium hover:underline">
                  {group.company_name}
                </Link>
              )}
              <p className="text-sm text-muted-foreground">
                {group.contracts_count} MoU · {rupiah(group.contracts_value)}
              </p>
            </div>

            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10">
                    <Checkbox
                      checked={groupSelected ? true : groupPartial ? 'indeterminate' : false}
                      onCheckedChange={() => toggleGroup(group.contracts)}
                      aria-label={`Pilih semua MoU ${group.company_name}`}
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
                {group.contracts.map((contract) => (
                  <TableRow key={contract.id}>
                    <TableCell>
                      <Checkbox
                        checked={selection.selected.has(contract.id)}
                        onCheckedChange={() => selection.toggle(contract.id)}
                        aria-label={`Pilih ${contract.number}`}
                      />
                    </TableCell>
                    <TableCell>
                      <Link href={show(contract.id)} className="font-medium hover:underline">
                        {contract.number}
                      </Link>
                    </TableCell>
                    <TableCell className="max-w-xs truncate">{contract.title}</TableCell>
                    <TableCell>
                      {formatDate(contract.start_date)} – {formatDate(contract.end_date)}
                    </TableCell>
                    <TableCell className="text-right font-medium">
                      {rupiah(contract.value)}
                    </TableCell>
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
                          ...(can['approve-documents']
                            ? [
                                {
                                  label: 'Tandatangani',
                                  icon: PenLine,
                                  disabledReason: SIGNABLE.includes(contract.status)
                                    ? undefined
                                    : 'Sudah lewat tahap tanda tangan.',
                                  onSelect: async () => {
                                    const confirmed = await confirm({
                                      title: `Tandai ${contract.number} sudah ditandatangani?`,
                                      description:
                                        'Status MoU berubah jadi Ditandatangani dan siap ditagihkan.',
                                      confirmLabel: 'Tandatangani',
                                    });

                                    if (confirmed) {
                                      router.post(sign(contract.id), {}, { preserveScroll: true });
                                    }
                                  },
                                },
                              ]
                            : []),
                          ...(can['manage-records']
                            ? [
                                {
                                  label: 'Hapus MoU',
                                  icon: Trash2,
                                  destructive: true,
                                  onSelect: async () => {
                                    const confirmed = await confirm({
                                      title: `Hapus MoU ${contract.number}?`,
                                      description:
                                        'Ruang lingkup dan lampirannya ikut terhapus; invoice yang sudah terbit tetap tersimpan.',
                                      confirmLabel: 'Hapus MoU',
                                      destructive: true,
                                    });

                                    if (confirmed) {
                                      router.delete(destroy(contract.id), { preserveScroll: true });
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
          </Card>
        );
      })}

      {confirmDialog}
    </div>
  );
}
