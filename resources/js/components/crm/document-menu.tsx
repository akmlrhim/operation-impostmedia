import { Link } from '@inertiajs/react';
import { ChevronDown, Plus } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps } from 'react';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Href = ComponentProps<typeof Link>['href'];

export type DocumentEntry = {
  id: number;
  href: Href;
  primary: string;
  secondary?: string;
  amount: string;
  status: string;
};

export function DocumentMenu({
  icon: Icon,
  entries,
  count,
  createHref,
  createLabel,
  emptyLabel,
  ariaLabel,
}: {
  icon: LucideIcon;
  entries: DocumentEntry[];
  count?: number;
  createHref: Href;
  createLabel: string;
  emptyLabel: string;
  ariaLabel: string;
}) {
  const total = count ?? entries.length;
  const truncated = entries.length < total;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className="gap-1.5 font-normal data-[state=open]:bg-accent"
          aria-label={`${ariaLabel} (${total})`}
        >
          <Icon className="size-4 text-muted-foreground" />
          <span>{total}</span>
          <ChevronDown className="size-3.5 text-muted-foreground" />
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="end" className="w-80">
        {entries.length === 0 && (
          <p className="px-2 py-3 text-center text-sm text-muted-foreground">{emptyLabel}</p>
        )}

        {entries.length > 0 && (
          <div className="max-h-72 overflow-y-auto">
            {entries.map((entry) => (
              <DropdownMenuItem key={entry.id} asChild>
                <Link href={entry.href} className="flex items-center gap-3">
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-xs font-medium">{entry.primary}</span>
                    {entry.secondary && (
                      <span className="block truncate text-xs text-muted-foreground">
                        {entry.secondary}
                      </span>
                    )}
                  </span>
                  <span className="shrink-0 space-y-1 text-right">
                    <span className="block text-xs font-medium">{entry.amount}</span>
                    <StatusBadge value={entry.status} className="text-[10px]" />
                  </span>
                </Link>
              </DropdownMenuItem>
            ))}
          </div>
        )}

        {truncated && (
          <p className="px-2 py-1.5 text-center text-xs text-muted-foreground">
            {entries.length} terbaru dari {total}. Buka halaman klien untuk selengkapnya.
          </p>
        )}

        {entries.length > 0 && <DropdownMenuSeparator />}

        <DropdownMenuItem asChild>
          <Link href={createHref} className="gap-2">
            <Plus className="size-4" />
            {createLabel}
          </Link>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
