import { usePage } from '@inertiajs/react';
import { ChevronDown, Monitor, Moon, Search, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { CommandPalette } from '@/components/command-palette';
import { NotificationMenu } from '@/components/notification-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useAppearance } from '@/hooks/use-appearance';
import type { Appearance } from '@/hooks/use-appearance';
import { useInitials } from '@/hooks/use-initials';
import type { BreadcrumbItem } from '@/types';

const APPEARANCES: { value: Appearance; label: string; icon: typeof Sun }[] = [
  { value: 'light', label: 'Terang', icon: Sun },
  { value: 'dark', label: 'Gelap', icon: Moon },
  { value: 'system', label: 'Ikut perangkat', icon: Monitor },
];

function AppearanceMenu() {
  const { appearance, updateAppearance } = useAppearance();
  const Current = APPEARANCES.find((item) => item.value === appearance)?.icon ?? Monitor;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        className="flex size-7 cursor-pointer items-center justify-center rounded-sm text-muted-foreground transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
        aria-label="Ganti tampilan terang atau gelap"
      >
        <Current aria-hidden className="size-4" />
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="min-w-40">
        {APPEARANCES.map((item) => (
          <DropdownMenuItem
            key={item.value}
            onSelect={() => updateAppearance(item.value)}
            className="cursor-pointer"
          >
            <item.icon aria-hidden className="mr-2 size-4" />
            {item.label}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function AppTopbar({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItem[] }) {
  const { auth, name } = usePage().props;
  const getInitials = useInitials();
  const [paletteOpen, setPaletteOpen] = useState(false);

  useEffect(() => {
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'k' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        setPaletteOpen((open) => !open);
      }
    }

    window.addEventListener('keydown', onKeyDown);

    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  return (
    <header className="sticky top-0 z-30 flex h-(--app-header-height) shrink-0 items-center gap-2 border-b border-sidebar-border bg-sidebar px-2 sm:px-3">
      <SidebarTrigger className="size-7 shrink-0 text-muted-foreground" />

      <div className="flex min-w-0 items-center gap-2">
        <AppLogoIcon className="size-6" />
        <span className="truncate text-[0.8125rem] font-semibold tracking-tight">{name}</span>
      </div>

      <div aria-hidden className="mx-1 hidden h-5 w-px shrink-0 bg-sidebar-border md:block" />

      <div className="hidden min-w-0 flex-1 md:block [&_a]:text-[0.8125rem] [&_li]:text-[0.8125rem]">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
      </div>

      <div className="ml-auto flex items-center gap-1 md:ml-0">
        <button
          type="button"
          onClick={() => setPaletteOpen(true)}
          className="flex h-7 cursor-pointer items-center gap-2 rounded-sm border border-sidebar-border bg-background px-2 text-xs text-muted-foreground transition-colors outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
        >
          <Search aria-hidden className="size-3.5" />
          <span className="hidden lg:inline">Lompat ke</span>
          <kbd className="hidden rounded-xs border border-sidebar-border px-1 font-sans text-[0.625rem] lg:inline">
            Ctrl K
          </kbd>
        </button>

        <NotificationMenu />

        <AppearanceMenu />

        {auth.user && (
          <DropdownMenu>
            <DropdownMenuTrigger
              className="flex h-7 cursor-pointer items-center gap-1.5 rounded-sm px-1 transition-colors outline-none hover:bg-sidebar-accent focus-visible:ring-[3px] focus-visible:ring-ring/50"
              data-test="topbar-user-menu"
            >
              <Avatar className="size-6">
                <AvatarImage
                  src={auth.user.avatar ?? undefined}
                  alt={auth.user.name}
                  referrerPolicy="no-referrer"
                />
                <AvatarFallback className="bg-accent text-[0.625rem] font-semibold">
                  {getInitials(auth.user.name)}
                </AvatarFallback>
              </Avatar>
              <span className="hidden max-w-32 truncate text-[0.8125rem] sm:inline">
                {auth.user.name}
              </span>
              <ChevronDown aria-hidden className="size-3.5 text-muted-foreground" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-56">
              <UserMenuContent user={auth.user} />
            </DropdownMenuContent>
          </DropdownMenu>
        )}
      </div>

      <CommandPalette open={paletteOpen} onOpenChange={setPaletteOpen} />
    </header>
  );
}
