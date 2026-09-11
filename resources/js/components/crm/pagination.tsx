import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/crm';

export function Pagination<T>({ meta }: { meta: Paginated<T> }) {
  if (meta.links.length <= 3) {
    return null;
  }

  return (
    <nav aria-label="Navigasi halaman" className="flex items-center justify-end">
      <div className="flex flex-wrap items-center gap-0.5">
        {meta.links.map((link) =>
          link.url === null ? (
            <span
              key={link.label}
              className="px-2 py-1 text-xs text-muted-foreground/40"
              dangerouslySetInnerHTML={{ __html: link.label }}
            />
          ) : (
            <Link
              key={link.label}
              href={link.url}
              preserveScroll
              className={cn(
                'min-w-7 rounded-sm px-2 py-1 text-center num text-xs transition-colors hover:bg-accent',
                link.active && 'bg-primary text-primary-foreground hover:bg-primary',
              )}
              dangerouslySetInnerHTML={{ __html: link.label }}
            />
          ),
        )}
      </div>
    </nav>
  );
}
