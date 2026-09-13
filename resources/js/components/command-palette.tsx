import { router, usePage } from '@inertiajs/react';
import { CornerDownLeft, Search } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { navGroupsFor, quickActions } from '@/lib/navigation';
import { cn, toUrl } from '@/lib/utils';

type Entry = {
  key: string;
  group: string;
  title: string;
  url: string;
  haystack: string;
  icon?: LucideIcon;
};

export function CommandPalette({
  open,
  onOpenChange,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const { auth, can } = usePage().props;
  const [query, setQuery] = useState('');
  const [cursor, setCursor] = useState(0);
  const listRef = useRef<HTMLDivElement>(null);

  const entries = useMemo<Entry[]>(() => {
    const fromNav = navGroupsFor(auth.user?.role, can).flatMap((group) =>
      group.items.map((item) => ({
        key: `nav-${item.title}`,
        group: group.label ?? 'Modul',
        title: item.title,
        url: toUrl(item.href),
        haystack: `${group.label ?? ''} ${item.title}`.toLowerCase(),
        icon: item.icon ?? undefined,
      })),
    );

    const fromActions = quickActions.map((action) => ({
      key: `action-${action.title}`,
      group: 'Tindakan cepat',
      title: action.title,
      url: action.href,
      haystack: `${action.title} ${action.keywords}`.toLowerCase(),
    }));

    return [...fromNav, ...fromActions];
  }, [auth.user?.role, can]);

  const results = useMemo(() => {
    const needle = query.trim().toLowerCase();

    if (needle === '') {
      return entries;
    }

    return entries.filter((entry) =>
      needle.split(/\s+/).every((word) => entry.haystack.includes(word)),
    );
  }, [entries, query]);

  useEffect(() => {
    listRef.current?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' });
  }, [cursor]);

  function change(open: boolean) {
    if (!open) {
      setQuery('');
      setCursor(0);
    }

    onOpenChange(open);
  }

  function search(value: string) {
    setQuery(value);
    setCursor(0);
  }

  function go(entry: Entry | undefined) {
    if (!entry) {
      return;
    }

    change(false);
    router.visit(entry.url);
  }

  let renderedGroup = '';

  return (
    <Dialog open={open} onOpenChange={change}>
      <DialogContent
        className="top-[12%] max-w-[calc(100%-1.5rem)] translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-xl [&>button]:hidden"
        onKeyDown={(event) => {
          if (event.key === 'ArrowDown') {
            event.preventDefault();
            setCursor((value) => (results.length === 0 ? 0 : (value + 1) % results.length));
          }

          if (event.key === 'ArrowUp') {
            event.preventDefault();
            setCursor((value) =>
              results.length === 0 ? 0 : (value - 1 + results.length) % results.length,
            );
          }

          if (event.key === 'Enter') {
            event.preventDefault();
            go(results[cursor]);
          }
        }}
      >
        <DialogTitle className="sr-only">Lompat ke modul</DialogTitle>

        <div className="flex h-11 items-center gap-2 border-b px-3">
          <Search aria-hidden className="size-4 shrink-0 text-muted-foreground" />
          <input
            autoFocus
            value={query}
            onChange={(event) => search(event.target.value)}
            placeholder="Ketik nama modul atau tindakan"
            aria-label="Cari modul atau tindakan"
            className="h-full w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
          />
        </div>

        <div ref={listRef} className="max-h-80 overflow-y-auto py-1">
          {results.length === 0 && (
            <p className="px-3 py-6 text-center text-sm text-muted-foreground">
              Tidak ada yang cocok.
            </p>
          )}

          {results.map((entry, index) => {
            const showGroup = entry.group !== renderedGroup;
            renderedGroup = entry.group;
            const Icon = entry.icon;

            return (
              <div key={entry.key}>
                {showGroup && (
                  <p className="px-3 pt-2 pb-1 text-[0.6875rem] font-semibold tracking-[0.08em] text-muted-foreground uppercase">
                    {entry.group}
                  </p>
                )}

                <button
                  type="button"
                  data-active={index === cursor}
                  onMouseMove={() => setCursor(index)}
                  onClick={() => go(entry)}
                  className={cn(
                    'flex w-full cursor-pointer items-center gap-2 px-3 py-1.5 text-left text-sm',
                    index === cursor ? 'bg-accent text-accent-foreground' : 'text-foreground',
                  )}
                >
                  {Icon && <Icon aria-hidden className="size-4 text-muted-foreground" />}
                  <span className="truncate">{entry.title}</span>
                  {index === cursor && (
                    <CornerDownLeft
                      aria-hidden
                      className="ml-auto size-3.5 text-muted-foreground"
                    />
                  )}
                </button>
              </div>
            );
          })}
        </div>
      </DialogContent>
    </Dialog>
  );
}
