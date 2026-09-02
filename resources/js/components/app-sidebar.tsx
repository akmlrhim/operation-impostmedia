import { Link, usePage } from '@inertiajs/react';
import {
  Building2,
  FileSignature,
  LayoutDashboard,
  LayoutGrid,
  Package,
  Receipt,
  Target,
  Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as clientsIndex } from '@/routes/clients';
import { index as contractsIndex } from '@/routes/contracts';
import { dashboard as crmDashboard } from '@/routes/crm';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as leadsIndex } from '@/routes/leads';
import { index as servicesIndex } from '@/routes/services';
import { index as usersIndex } from '@/routes/users';
import type { NavGroup } from '@/types';

const navGroups: NavGroup[] = [
  {
    items: [{ title: 'General', href: dashboard(), icon: LayoutGrid }],
  },
  {
    label: 'CRM',
    items: [
      { title: 'Dashboard', href: crmDashboard(), icon: LayoutDashboard },
      { title: 'Leads', href: leadsIndex(), icon: Target },
      { title: 'Klien', href: clientsIndex(), icon: Building2 },
      { title: 'MoU & Kontrak', href: contractsIndex(), icon: FileSignature },
      { title: 'Invoice', href: invoicesIndex(), icon: Receipt },
      { title: 'Layanan', href: servicesIndex(), icon: Package },
    ],
  },
];

const superuserGroup: NavGroup = {
  label: 'Pengaturan',
  items: [{ title: 'User', href: usersIndex(), icon: Users }],
};

export function AppSidebar() {
  const { auth } = usePage().props;
  const groups = auth.user?.role === 'superuser' ? [...navGroups, superuserGroup] : navGroups;

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={dashboard()} prefetch>
                <AppLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent className="gap-4">
        <NavMain groups={groups} />
      </SidebarContent>

      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
