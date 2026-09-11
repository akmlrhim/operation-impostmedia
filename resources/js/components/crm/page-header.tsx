import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export function PageHeader({
  title,
  description,
  backHref,
  meta,
  actions,
}: {
  title: string;
  description?: string;
  backHref?: string;
  meta?: ReactNode;
  actions?: ReactNode;
}) {
  return (
    <header className="sticky top-(--app-header-height) z-10 flex min-h-11 flex-wrap items-center gap-x-3 gap-y-2 border-b border-border bg-card px-3 py-2 sm:px-4">
      {backHref && (
        <Button variant="ghost" size="icon-sm" className="-ml-1 shrink-0" asChild>
          <Link href={backHref} aria-label="Kembali">
            <ArrowLeft className="size-4" />
          </Link>
        </Button>
      )}

      <div className="flex min-w-0 flex-col">
        <div className="flex min-w-0 flex-wrap items-center gap-2">
          <h1 className="truncate text-base leading-tight font-semibold tracking-tight">{title}</h1>
          {meta}
        </div>

        {description && <p className="truncate text-xs text-muted-foreground">{description}</p>}
      </div>

      {actions && <div className="ml-auto flex flex-wrap items-center gap-2">{actions}</div>}
    </header>
  );
}
