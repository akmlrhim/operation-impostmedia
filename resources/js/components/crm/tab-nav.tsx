import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Href = ComponentProps<typeof Link>['href'];

export type TabItem = {
  value: string;
  label: string;
  href: Href;
  count?: number;
};

export function TabNav({
  tabs,
  active,
  label = 'Bagian halaman',
}: {
  tabs: TabItem[];
  active: string;
  label?: string;
}) {
  return (
    <div className="overflow-x-auto overflow-y-hidden">
      <nav className="flex min-w-max gap-4 border-b border-border" aria-label={label}>
        {tabs.map((tab) => {
          const current = tab.value === active;

          return (
            <Link
              key={tab.value}
              href={tab.href}
              aria-current={current ? 'page' : undefined}
              className={cn(
                'flex items-center gap-1.5 border-b-2 pb-1.5 text-[0.8125rem] font-medium transition-colors',
                current
                  ? 'border-foreground text-foreground'
                  : 'border-transparent text-muted-foreground hover:text-foreground',
              )}
            >
              {tab.label}
              {tab.count !== undefined && (
                <span
                  className={cn(
                    'rounded-sm px-1 py-px num text-[0.6875rem]',
                    current ? 'bg-accent text-foreground' : 'bg-muted text-muted-foreground',
                  )}
                >
                  {tab.count}
                </span>
              )}
            </Link>
          );
        })}
      </nav>
    </div>
  );
}
