import { Link, router } from '@inertiajs/react';
import { ArrowRightLeft, Eye, Pencil, Trash2 } from 'lucide-react';
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
import { convert, destroy, destroyBulk, exportMethod as exportLeads, show } from '@/routes/leads';
import type { LeadCard, LeadStageTable, Option } from '@/types/crm';

type LeadRow = LeadCard & { stage: { id: number; name: string; color: string } | null };

const COLUMN_COUNT = 8;

function Blank() {
  return <span className="text-muted-foreground">-</span>;
}

export function LeadTable({
  stage,
  leads,
  statuses,
  temperatures,
  sort,
  direction,
  onSort,
  onEdit,
}: {
  stage: LeadStageTable;
  leads: LeadRow[];
  statuses: Option[];
  temperatures: Option[];
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

      <Card className="overflow-hidden py-0">
        <div className="flex flex-wrap items-center gap-2 border-b bg-muted/40 px-4 py-3">
          <span
            aria-hidden
            className="size-2.5 rounded-full"
            style={{ backgroundColor: stage.color ?? 'var(--border)' }}
          />
          <h3 className="text-sm font-semibold">{stage.name}</h3>
          <span className="num rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
            {leads.length}
          </span>
        </div>

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
                label="Klien"
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
                label="Temperature"
                active={sort === 'temperature'}
                direction={direction}
                onClick={() => onSort('temperature')}
              />
              <SortableTableHead
                label="Tindak lanjut"
                active={sort === 'next_action_date'}
                direction={direction}
                onClick={() => onSort('next_action_date')}
              />
              <SortableTableHead
                label="Nilai"
                active={sort === 'estimated_value'}
                direction={direction}
                onClick={() => onSort('estimated_value')}
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
            {leads.length === 0 && (
              <TableRow>
                <TableCell
                  colSpan={COLUMN_COUNT}
                  className="py-8 text-center text-muted-foreground"
                >
                  Belum ada lead pada tahap ini.
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
                  <Link
                    href={show(lead.id)}
                    className="block max-w-64 truncate font-medium hover:underline"
                    title={lead.company_name}
                  >
                    {lead.company_name}
                  </Link>
                  {lead.industry && (
                    <span className="block max-w-64 truncate text-xs text-muted-foreground">
                      {lead.industry}
                    </span>
                  )}
                </TableCell>
                <TableCell>
                  {lead.contact_name || lead.phone ? (
                    <div className="max-w-44">
                      {lead.contact_name && <div className="truncate">{lead.contact_name}</div>}
                      {lead.phone && (
                        <div className="truncate text-xs text-muted-foreground">{lead.phone}</div>
                      )}
                    </div>
                  ) : (
                    <Blank />
                  )}
                </TableCell>
                <TableCell>
                  <StatusBadge value={lead.temperature} options={temperatures} />
                </TableCell>
                <TableCell>
                  {lead.next_action_date ? (
                    <div className="whitespace-nowrap">
                      <div>{formatDate(lead.next_action_date)}</div>
                      <div className="text-xs text-muted-foreground">
                        {relativeDueLabel(lead.next_action_date)}
                      </div>
                    </div>
                  ) : (
                    <Blank />
                  )}
                </TableCell>
                <TableCell className="text-right font-medium whitespace-nowrap">
                  {rupiah(lead.estimated_value)}
                </TableCell>
                <TableCell>
                  <StatusBadge value={lead.status} options={statuses} />
                </TableCell>
                <TableCell>
                  <RowActions
                    label={lead.company_name}
                    actions={[
                      {
                        label: 'Buka detail',
                        icon: Eye,
                        onSelect: () => router.visit(show(lead.id)),
                      },
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