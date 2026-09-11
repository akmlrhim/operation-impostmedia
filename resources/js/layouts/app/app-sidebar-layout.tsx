import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppTopbar } from '@/components/app-topbar';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
  return (
    <AppShell variant="sidebar">
      <AppTopbar breadcrumbs={breadcrumbs} />

      <div className="flex w-full flex-1">
        <AppSidebar />
        <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
          {children}
        </AppContent>
      </div>
    </AppShell>
  );
}
