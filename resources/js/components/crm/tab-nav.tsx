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

export function TabNav({ tabs, active }: { tabs: TabItem[]; active: string }) {
  return (
    <div className="overflow-x-auto overflow-y-hidden">
      <nav className="flex min-w-max gap-1 border-b" aria-label="Bagian halaman klien">
        {tabs.map((tab) => {
          const current = tab.value === active;

          return (
            <Link
              key={tab.value}
              href={tab.href}
              aria-current={current ? 'page' : undefined}
              className={cn(
                'flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                current
                  ? 'border-primary text-foreground'
                  : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
              )}
            >
              {tab.label}
              {tab.count !== undefined && (
                <span
                  className={cn(
                    'rounded-full px-1.5 py-0.5 text-xs',
                    current ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground',
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
