import { Link } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Href = ComponentProps<typeof Link>['href'];

export type RowAction = {
  label: string;
  icon: LucideIcon;
  href?: Href;
  onSelect?: () => void;
  destructive?: boolean;
  disabledReason?: string;
};

export function RowActions({ label, actions }: { label: string; actions: RowAction[] }) {
  const normal = actions.filter((action) => !action.destructive);
  const destructive = actions.filter((action) => action.destructive);

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="icon"
          className="size-8 text-muted-foreground data-[state=open]:bg-accent"
          aria-label={`Aksi ${label}`}
        >
          <MoreVertical className="size-4" />
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="end" className="w-56">
        {normal.map((action) => (
          <ActionItem key={action.label} action={action} />
        ))}

        {normal.length > 0 && destructive.length > 0 && <DropdownMenuSeparator />}

        {destructive.map((action) => (
          <ActionItem key={action.label} action={action} />
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

function ActionItem({ action }: { action: RowAction }) {
  const { label, icon: Icon, href, onSelect, destructive, disabledReason } = action;

  if (disabledReason) {
    return (
      <DropdownMenuItem disabled className="flex-col items-start gap-0">
        <span className="flex items-center gap-2">
          <Icon className="size-4" />
          {label}
        </span>
        <span className="pl-6 text-xs">{disabledReason}</span>
      </DropdownMenuItem>
    );
  }

  if (href) {
    return (
      <DropdownMenuItem asChild variant={destructive ? 'destructive' : 'default'}>
        <Link href={href}>
          <Icon className="size-4" />
          {label}
        </Link>
      </DropdownMenuItem>
    );
  }

  return (
    <DropdownMenuItem
      variant={destructive ? 'destructive' : 'default'}
      onSelect={() => onSelect?.()}
    >
      <Icon className="size-4" />
      {label}
    </DropdownMenuItem>
  );
}
