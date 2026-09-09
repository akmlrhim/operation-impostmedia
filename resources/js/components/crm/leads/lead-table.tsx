import { Link, router } from '@inertiajs/react';
import { ArrowRightLeft, ExternalLink, Eye, Pencil, Trash2 } from 'lucide-react';
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
import { convert, destroy, destroyBulk, exportMethod as exportLeads, show } from '@/routes/leads';
import type { LeadCard, Option } from '@/types/crm';

type LeadRow = LeadCard & { stage: { id: number; name: string; color: string } | null };

const COLUMN_COUNT = 21;

function Truncated({ value, width }: { value: string | null; width: string }) {
  if (!value) {
    return <span className="text-muted-foreground">-</span>;
  }

  return (
    <span className={`block truncate ${width}`} title={value}>
      {value}
    </span>
  );
}

export function LeadTable({
  leads,
  statuses,
  sources,
  temperatures,
  sort,
  direction,
  onSort,
  onEdit,
}: {
  leads: LeadRow[];
  statuses: Option[];
  sources: Option[];
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

      <Card className="overflow-hidden rounded-sm py-0">
        <Table className="min-w-[2200px]">
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
                label="Date In"
                active={sort === 'date_in'}
                direction={direction}
                onClick={() => onSort('date_in')}
              />
              <SortableTableHead
                label="Client Name"
                active={sort === 'company_name'}
                direction={direction}
                onClick={() => onSort('company_name')}
              />
              <SortableTableHead
                label="Industry"
                active={sort === 'industry'}
                direction={direction}
                onClick={() => onSort('industry')}
              />
              <SortableTableHead
                label="Contact Person"
                active={sort === 'contact_name'}
                direction={direction}
                onClick={() => onSort('contact_name')}
              />
              <TableHead>Contact Info</TableHead>
              <SortableTableHead
                label="Asal Daerah"
                active={sort === 'region'}
                direction={direction}
                onClick={() => onSort('region')}
              />
              <SortableTableHead
                label="Source"
                active={sort === 'source'}
                direction={direction}
                onClick={() => onSort('source')}
              />
              <TableHead>PIC</TableHead>
              <TableHead>PIC Impost</TableHead>
              <TableHead>Service Needed</TableHead>
              <TableHead>Invoice Terakhir</TableHead>
              <SortableTableHead
                label="Estimated Value (Rp)"
                active={sort === 'estimated_value'}
                direction={direction}
                onClick={() => onSort('estimated_value')}
                className="text-right"
              />
              <SortableTableHead
                label="Last Contact Date"
                active={sort === 'last_contact_date'}
                direction={direction}
                onClick={() => onSort('last_contact_date')}
              />
              <SortableTableHead
                label="Next Action Date"
                active={sort === 'next_action_date'}
                direction={direction}
                onClick={() => onSort('next_action_date')}
              />
              <TableHead>Next Action</TableHead>
              <SortableTableHead
                label="Temperature"
                active={sort === 'temperature'}
                direction={direction}
                onClick={() => onSort('temperature')}
              />
              <TableHead>Notes</TableHead>
              <TableHead>Link Folder</TableHead>
              <SortableTableHead
                label="Deal Status"
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
                  className="py-10 text-center text-muted-foreground"
                >
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
                <TableCell className="whitespace-nowrap">
                  {lead.date_in ? formatDate(lead.date_in) : '-'}
                </TableCell>
                <TableCell>
                  <Link
                    href={show(lead.id)}
                    className="block max-w-56 truncate font-medium hover:underline"
                    title={lead.company_name}
                  >
                    {lead.company_name}
                  </Link>
                </TableCell>
                <TableCell>
                  <Truncated value={lead.industry} width="max-w-40" />
                </TableCell>
                <TableCell>
                  <Truncated value={lead.contact_name} width="max-w-40" />
                </TableCell>
                <TableCell>
                  {lead.phone || lead.email ? (
                    <div className="max-w-52">
                      {lead.phone && <div className="truncate">{lead.phone}</div>}
                      {lead.email && (
                        <div className="truncate text-muted-foreground" title={lead.email}>
                          {lead.email}
                        </div>
                      )}
                    </div>
                  ) : (
                    <span className="text-muted-foreground">-</span>
                  )}
                </TableCell>
                <TableCell>
                  <Truncated value={lead.region} width="max-w-36" />
                </TableCell>
                <TableCell>
                  {lead.source ? (
                    <StatusBadge value={lead.source} options={sources} />
                  ) : (
                    <span className="text-muted-foreground">-</span>
                  )}
                </TableCell>
                <TableCell>
                  <Truncated value={lead.pic} width="max-w-36" />
                </TableCell>
                <TableCell>
                  <Truncated value={lead.pic_impost} width="max-w-36" />
                </TableCell>
                <TableCell>
                  <Truncated
                    value={
                      lead.service_packages.length === 0
                        ? null
                        : lead.service_packages.map((pkg) => pkg.name).join(', ')
                    }
                    width="max-w-48"
                  />
                </TableCell>
                <TableCell className="whitespace-nowrap">
                  {lead.last_invoice ? (
                    <span title={lead.last_invoice.issue_date ?? undefined}>
                      {lead.last_invoice.number}
                    </span>
                  ) : (
                    <span className="text-muted-foreground">-</span>
                  )}
                </TableCell>
                <TableCell className="text-right font-medium whitespace-nowrap">
                  {rupiah(lead.estimated_value)}
                </TableCell>
                <TableCell className="whitespace-nowrap">
                  {lead.last_contact_date ? formatDate(lead.last_contact_date) : '-'}
                </TableCell>
                <TableCell className="whitespace-nowrap">
                  {lead.next_action_date ? formatDate(lead.next_action_date) : '-'}
                </TableCell>
                <TableCell>
                  <Truncated value={lead.next_action} width="max-w-48" />
                </TableCell>
                <TableCell>
                  <StatusBadge value={lead.temperature} options={temperatures} />
                </TableCell>
                <TableCell>
                  <Truncated value={lead.notes} width="max-w-64" />
                </TableCell>
                <TableCell>
                  {lead.folder_url ? (
                    <a
                      href={lead.folder_url}
                      target="_blank"
                      rel="noreferrer noopener"
                      className="inline-flex items-center gap-1 hover:underline"
                      title={lead.folder_url}
                    >
                      <ExternalLink aria-hidden className="size-3.5 shrink-0" />
                      Folder
                    </a>
                  ) : (
                    <span className="text-muted-foreground">-</span>
                  )}
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
