import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editCompany } from '@/routes/company';
import { edit } from '@/routes/profile';
import type { NavItem } from '@/types';

const settingsNavItems: NavItem[] = [
  { title: 'Perusahaan', href: editCompany() },
  { title: 'Profil saya', href: edit() },
  { title: 'Tampilan', href: editAppearance() },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
  const { isCurrentOrParentUrl } = useCurrentUrl();

  return (
    <>
      <PageHeader title="Pengaturan" />

      <PageBody>
        <div className="grid gap-4 lg:grid-cols-[11rem_minmax(0,1fr)]">
          <nav
            aria-label="Bagian pengaturan"
            className="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible"
          >
            {settingsNavItems.map((item) => (
              <Link
                key={toUrl(item.href)}
                href={item.href}
                aria-current={isCurrentOrParentUrl(item.href) ? 'page' : undefined}
                className={cn(
                  'rounded-sm px-2.5 py-1.5 text-[0.8125rem] whitespace-nowrap transition-colors',
                  isCurrentOrParentUrl(item.href)
                    ? 'bg-accent font-semibold text-accent-foreground'
                    : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                )}
              >
                {item.title}
              </Link>
            ))}
          </nav>

          <section className="max-w-3xl space-y-6">{children}</section>
        </div>
      </PageBody>
    </>
  );
}
