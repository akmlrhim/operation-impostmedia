import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck, FileSignature, Receipt, Target, UserPlus, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useRealtime } from '@/hooks/use-realtime';
import { relativeTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { read, readAll } from '@/routes/notifications';
import type { AppNotification } from '@/types';

const icons: Record<string, LucideIcon> = {
  assigned: UserPlus,
  contract: FileSignature,
  invoice: Receipt,
  lead: Target,
  user: Users,
};

export function NotificationMenu() {
  const { notifications } = usePage().props;
  const [open, setOpen] = useState(false);

  useRealtime(['notifications'], ['notifications']);

  const unread = notifications?.unread ?? 0;
  const items = notifications?.items ?? [];

  function go(item: AppNotification) {
    setOpen(false);

    if (item.read) {
      router.visit(item.url);

      return;
    }

    router.post(read(item.id).url);
  }

  return (
    <DropdownMenu open={open} onOpenChange={setOpen}>
      <DropdownMenuTrigger
        className="relative flex size-7 cursor-pointer items-center justify-center rounded-sm text-muted-foreground transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
        aria-label={unread > 0 ? `Notifikasi, ${unread} belum dibaca` : 'Notifikasi'}
      >
        <Bell aria-hidden className="size-4" />
        {unread > 0 && (
          <span className="absolute -top-0.5 -right-0.5 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-red-600 px-1 num text-[0.5625rem] font-semibold text-white">
            {unread > 9 ? '9+' : unread}
          </span>
        )}
      </DropdownMenuTrigger>

      <DropdownMenuContent align="end" className="w-80 p-0">
        <div className="flex items-center justify-between gap-2 border-b bg-panel-header px-3 py-2">
          <span className="text-[0.8125rem] font-semibold">Notifikasi</span>

          {unread > 0 && (
            <button
              type="button"
              onClick={() => router.post(readAll().url, {}, { preserveScroll: true })}
              className="flex cursor-pointer items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
            >
              <CheckCheck aria-hidden className="size-3.5" />
              Tandai sudah dibaca
            </button>
          )}
        </div>

        <div className="max-h-96 overflow-y-auto">
          {items.length === 0 ? (
            <p className="px-3 py-6 text-center text-sm text-muted-foreground">
              Belum ada kabar untuk Anda.
            </p>
          ) : (
            items.map((item) => {
              const Icon = icons[item.kind] ?? Bell;

              return (
                <button
                  key={item.id}
                  type="button"
                  onClick={() => go(item)}
                  className={cn(
                    'flex w-full cursor-pointer items-start gap-2.5 border-b border-border/60 px-3 py-2 text-left transition-colors last:border-b-0 hover:bg-accent',
                    !item.read && 'bg-accent/40',
                  )}
                >
                  <Icon
                    aria-hidden
                    className={cn(
                      'mt-0.5 size-4 shrink-0',
                      item.read ? 'text-muted-foreground' : 'text-foreground',
                    )}
                  />

                  <span className="min-w-0 flex-1">
                    <span
                      className={cn(
                        'block truncate text-[0.8125rem]',
                        item.read ? 'font-medium' : 'font-semibold',
                      )}
                    >
                      {item.title}
                    </span>
                    <span className="block truncate text-xs text-muted-foreground">
                      {item.subtitle}
                    </span>
                    <span className="block text-xs text-muted-foreground/80">
                      {relativeTime(item.at)}
                    </span>
                  </span>

                  {!item.read && (
                    <span
                      aria-hidden
                      className="mt-1.5 size-1.5 shrink-0 rounded-full bg-red-600"
                    />
                  )}
                </button>
              );
            })
          )}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
