import { usePage } from '@inertiajs/react';
import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarRail } from '@/components/ui/sidebar';
import { navGroupsFor } from '@/lib/navigation';

export function AppSidebar() {
  const { auth, can } = usePage().props;

  return (
    <Sidebar collapsible="icon" variant="sidebar">
      <SidebarContent className="gap-0 overflow-x-hidden py-1">
        <NavMain groups={navGroupsFor(auth.user?.role, can)} />
      </SidebarContent>

      <SidebarRail />
    </Sidebar>
  );
}
