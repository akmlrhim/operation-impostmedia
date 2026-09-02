import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/crm';

export function Pagination<T>({ meta }: { meta: Paginated<T> }) {
  if (meta.links.length <= 3) {
    return null;
  }

  return (
    <nav
      aria-label="Navigasi halaman"
      className="flex flex-wrap items-center justify-between gap-3"
    >
      <p className="text-sm text-muted-foreground">
        Menampilkan {meta.from ?? 0}–{meta.to ?? 0} dari {meta.total}
      </p>

      <div className="flex flex-wrap gap-1">
        {meta.links.map((link) =>
          link.url === null ? (
            <span
              key={link.label}
              className="rounded-md px-3 py-1.5 text-sm text-muted-foreground/50"
              dangerouslySetInnerHTML={{ __html: link.label }}
            />
          ) : (
            <Link
              key={link.label}
              href={link.url}
              preserveScroll
              className={cn(
                'rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent',
                link.active && 'border-primary bg-primary text-primary-foreground hover:bg-primary',
              )}
              dangerouslySetInnerHTML={{ __html: link.label }}
            />
          ),
        )}
      </div>
    </nav>
  );
}
