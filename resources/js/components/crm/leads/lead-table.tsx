import { router } from '@inertiajs/react';
import { ArrowRightLeft, Pencil, Trash2 } from 'lucide-react';
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
import { convert, destroy, destroyBulk, exportMethod as exportLeads } from '@/routes/leads';
import type { LeadCard, Option } from '@/types/crm';

type LeadRow = LeadCard & { stage: { id: number; name: string; color: string } | null };

export function LeadTable({
  leads,
  priorities,
  statuses,
  sort,
  direction,
  onSort,
  onEdit,
}: {
  leads: LeadRow[];
  priorities: Option[];
  statuses: Option[];
  sort: string;
  direction: 'asc' | 'desc';
  onSort: (column: string) => void;
  onEdit: (lead: LeadRow) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const selection = useRowSelection(leads);

  return (
    <div className="flex flex-col gap-3">
      <BulkActionsBar
        count={selection.count}
        noun="lead"
        exportHref={exportLeads({ query: { ids: Array.from(selection.selected) } }).url}
        deleteTitle={`Hapus ${selection.count} lead?`}
        deleteDescription="Riwayat aktivitas lead yang dihapus ikut hilang."
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
                  aria-label="Pilih semua lead"
                />
              </TableHead>
              <SortableTableHead
                label="Perusahaan"
                active={sort === 'company_name'}
                direction={direction}
                onClick={() => onSort('company_name')}
              />
              <SortableTableHead
                label="Kontak"
                active={sort === 'contact_name'}
                direction={direction}
                onClick={() => onSort('contact_name')}
              />
              <SortableTableHead
                label="Stage"
                active={sort === 'stage'}
                direction={direction}
                onClick={() => onSort('stage')}
              />
              <SortableTableHead
                label="Prioritas"
                active={sort === 'priority'}
                direction={direction}
                onClick={() => onSort('priority')}
              />
              <SortableTableHead
                label="Estimasi Nilai"
                active={sort === 'estimated_value'}
                direction={direction}
                onClick={() => onSort('estimated_value')}
                className="text-right"
              />
              <SortableTableHead
                label="Target Closing"
                active={sort === 'expected_close_date'}
                direction={direction}
                onClick={() => onSort('expected_close_date')}
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
            {leads.length === 0 && (
              <TableRow>
                <TableCell colSpan={9} className="py-10 text-center text-muted-foreground">
                  Belum ada lead yang cocok.
                </TableCell>
              </TableRow>
            )}

            {leads.map((lead) => (
              <TableRow key={lead.id}>
                <TableCell>
                  <Checkbox
                    checked={selection.selected.has(lead.id)}
                    onCheckedChange={() => selection.toggle(lead.id)}
                    aria-label={`Pilih ${lead.company_name}`}
                  />
                </TableCell>
                <TableCell>
                  <button
                    type="button"
                    onClick={() => onEdit(lead)}
                    className="font-medium hover:underline"
                  >
                    {lead.company_name}
                  </button>
                </TableCell>
                <TableCell>
                  <div>{lead.contact_name}</div>
                  {(lead.phone || lead.email) && (
                    <div className="text-xs text-muted-foreground">{lead.phone || lead.email}</div>
                  )}
                </TableCell>
                <TableCell>
                  {lead.stage && (
                    <span className="inline-flex items-center gap-1.5">
                      <span
                        aria-hidden
                        className="size-2 shrink-0 rounded-full"
                        style={{ backgroundColor: lead.stage.color }}
                      />
                      <span className="truncate">{lead.stage.name}</span>
                    </span>
                  )}
                </TableCell>
                <TableCell>
                  <StatusBadge value={lead.priority} options={priorities} />
                </TableCell>
                <TableCell className="text-right font-medium">
                  {rupiah(lead.estimated_value)}
                </TableCell>
                <TableCell className="text-xs text-muted-foreground">
                  {lead.expected_close_date ? formatDate(lead.expected_close_date) : '-'}
                </TableCell>
                <TableCell>
                  <StatusBadge value={lead.status} options={statuses} />
                </TableCell>
                <TableCell>
                  <RowActions
                    label={lead.company_name}
                    actions={[
                      { label: 'Ubah lead', icon: Pencil, onSelect: () => onEdit(lead) },
                      ...(lead.converted_client_id === null
                        ? [
                            {
                              label: 'Jadikan klien',
                              icon: ArrowRightLeft,
                              onSelect: () => router.post(convert(lead.id)),
                            },
                          ]
                        : []),
                      {
                        label: 'Hapus lead',
                        icon: Trash2,
                        destructive: true,
                        onSelect: async () => {
                          const confirmed = await confirm({
                            title: `Hapus lead ${lead.company_name}?`,
                            description: 'Riwayat aktivitas lead ini ikut terhapus.',
                            confirmLabel: 'Hapus lead',
                            destructive: true,
                          });

                          if (confirmed) {
                            router.delete(destroy(lead.id), { preserveScroll: true });
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
