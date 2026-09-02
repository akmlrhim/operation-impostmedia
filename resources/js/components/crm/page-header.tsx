import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export function PageHeader({
  title,
  backHref,
  actions,
}: {
  title: string;
  backHref?: string;
  actions?: ReactNode;
}) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-3">
      <div className="flex items-center gap-2">
        {backHref && (
          <Button variant="ghost" size="icon" className="-ml-2 shrink-0" asChild>
            <Link href={backHref} aria-label="Kembali">
              <ArrowLeft className="size-4" />
            </Link>
          </Button>
        )}

        <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
      </div>

      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  );
}
