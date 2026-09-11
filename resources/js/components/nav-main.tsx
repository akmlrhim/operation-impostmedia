import { Link } from '@inertiajs/react';
import { Fragment, useMemo } from 'react';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarSeparator,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';
import type { NavGroup } from '@/types';

function hrefPath(href: string): string {
  if (!href.startsWith('http')) {
    return href;
  }

  try {
    return new URL(href).pathname;
  } catch {
    return href;
  }
}

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
  const { currentUrl } = useCurrentUrl();

  const activeHref = useMemo(() => {
    const hrefs = groups.flatMap((group) => group.items.map((item) => hrefPath(toUrl(item.href))));
    const matches = hrefs.filter((path) => {
      if (path === currentUrl) {
        return true;
      }

      return currentUrl.startsWith(path.endsWith('/') ? path : `${path}/`);
    });

    return matches.sort((a, b) => b.length - a.length)[0];
  }, [currentUrl, groups]);

  return (
    <>
      {groups.map((group, index) => (
        <Fragment key={group.label ?? `group-${index}`}>
          {index > 0 && <SidebarSeparator className="my-1" />}

          <SidebarGroup className="py-1">
            {group.label && <SidebarGroupLabel>{group.label}</SidebarGroupLabel>}
            <SidebarMenu>
              {group.items.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton
                    asChild
                    isActive={hrefPath(toUrl(item.href)) === activeHref}
                    tooltip={{ children: item.title }}
                  >
                    <Link href={item.href} prefetch>
                      {item.icon && <item.icon />}
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroup>
        </Fragment>
      ))}
    </>
  );
}