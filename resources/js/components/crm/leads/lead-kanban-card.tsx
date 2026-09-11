import { Link } from '@inertiajs/react';
import { ArrowRightLeft, CalendarDays, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { InitialBadge } from '@/components/crm/initial-badge';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { daysFromToday, rupiah, rupiahCompact, shortDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/leads';
import type { LeadCard, Option } from '@/types/crm';

function MetaChip({
  icon: Icon,
  children,
  title,
  overdue = false,
}: {
  icon?: LucideIcon;
  children: ReactNode;
  title?: string;
  overdue?: boolean;
}) {
  return (
    <span
      title={title}
      className={cn(
        'inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-xs',
        overdue
          ? 'border-red-200 text-red-700 dark:border-red-900 dark:text-red-300'
          : 'text-muted-foreground',
      )}
    >
      {Icon && <Icon aria-hidden className="size-3 shrink-0" />}
      {children}
    </span>
  );
}

export function LeadKanbanCard({
  lead,
  temperatures,
  onEdit,
  onConvert,
  onDelete,
}: {
  lead: LeadCard;
  temperatures: Option[];
  onEdit: () => void;
  onConvert: () => void;
  onDelete: () => void;
}) {
  const dueInDays = daysFromToday(lead.next_action_date);

  return (
    <article className="group rounded-xl border bg-card p-3 shadow-xs transition-shadow duration-150 hover:shadow-md motion-reduce:transition-none">
      <div className="flex items-center gap-2">
        <InitialBadge name={lead.company_name} />
        <Link
          href={show(lead.id)}
          draggable={false}
          className="min-w-0 truncate text-xs text-muted-foreground hover:text-foreground hover:underline"
        >
          {lead.company_name}
        </Link>

        <div className="ml-auto flex shrink-0 items-center gap-0.5">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="icon"
                aria-label={`Tindakan untuk ${lead.company_name}`}
                className="size-6 text-muted-foreground opacity-0 transition-opacity duration-150 group-focus-within:opacity-100 group-hover:opacity-100 data-[state=open]:opacity-100 motion-reduce:transition-none [@media(hover:none)]:opacity-100"
              >
                <MoreHorizontal className="size-3.5" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onSelect={onEdit}>
                <Pencil className="size-3.5" />
                Ubah lead
              </DropdownMenuItem>

              {lead.converted_client_id === null && (
                <DropdownMenuItem onSelect={onConvert}>
                  <ArrowRightLeft className="size-3.5" />
                  Jadikan klien
                </DropdownMenuItem>
              )}

              <DropdownMenuItem variant="destructive" onSelect={onDelete}>
                <Trash2 className="size-3.5" />
                Hapus lead
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <Link
        href={show(lead.id)}
        draggable={false}
        className="mt-2 block w-full truncate text-left text-sm font-medium hover:underline"
      >
        {lead.contact_name}
      </Link>

      <div className="mt-2.5 flex flex-wrap items-center gap-1.5">
        <StatusBadge value={lead.temperature} options={temperatures} />

        {lead.vacancy_position && <MetaChip title="Posisi loker">{lead.vacancy_position}</MetaChip>}

        {lead.next_action_date && (
          <MetaChip
            icon={CalendarDays}
            overdue={dueInDays !== null && dueInDays < 0}
            title={lead.next_action ?? undefined}
          >
            {shortDate(lead.next_action_date)}
          </MetaChip>
        )}

        <MetaChip title={rupiah(lead.estimated_value)}>
          {rupiahCompact(lead.estimated_value)}
        </MetaChip>
      </div>
    </article>
  );
}
