import {
  Building2,
  FileSignature,
  Gauge,
  LayoutGrid,
  Package,
  PieChart,
  Receipt,
  Settings2,
  ShieldCheck,
  Target,
  Users,
  Wallet,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as accessIndex } from '@/routes/access';
import { index as clientsIndex } from '@/routes/clients';
import { edit as companyEdit } from '@/routes/company';
import { create as contractsCreate, index as contractsIndex } from '@/routes/contracts';
import { dashboard as crmDashboard } from '@/routes/crm';
import { dashboard as financeDashboard, index as financeIndex } from '@/routes/finance';
import { create as invoicesCreate, index as invoicesIndex } from '@/routes/invoices';
import { index as leadsIndex } from '@/routes/leads';
import { index as servicesIndex } from '@/routes/services';
import { index as usersIndex } from '@/routes/users';
import type { NavGroup, UserCan, UserRole } from '@/types';

export function navGroupsFor(role: UserRole | undefined, can: Partial<UserCan>): NavGroup[] {
  const groups: NavGroup[] = [
    {
      items: [{ title: 'Beranda', href: dashboard(), icon: LayoutGrid }],
    },
    {
      label: 'Penjualan',
      items: [
        { title: 'Ringkasan', href: crmDashboard(), icon: Gauge },
        { title: 'Leads', href: leadsIndex(), icon: Target },
        { title: 'Klien', href: clientsIndex(), icon: Building2 },
        { title: 'MoU & Kontrak', href: contractsIndex(), icon: FileSignature },
      ],
    },
  ];

  const finance: NavGroup = {
    label: 'Keuangan',
    items: [{ title: 'Invoice', href: invoicesIndex(), icon: Receipt }],
  };

  const masterData: NavGroup = {
    label: 'Master Data',
    items: [{ title: 'Layanan', href: servicesIndex(), icon: Package }],
  };

  const settings: NavGroup = {
    label: 'Pengaturan',
    items: [],
  };

  const canManageMasterData =
    can['manage-services'] || can['manage-lead-stages'] || can['manage-company-settings'];

  if (can['view-finance']) {
    finance.items.push({ title: 'Ringkasan', href: financeDashboard(), icon: PieChart });
    finance.items.push({ title: 'Pemasukan & Pengeluaran', href: financeIndex(), icon: Wallet });
  }

  if (canManageMasterData) {
    groups.push(masterData);
  }

  if (can['manage-company-settings']) {
    settings.items.push({ title: 'Perusahaan', href: companyEdit(), icon: Settings2 });
  }

  if (can['manage-users']) {
    settings.items.push({ title: 'Pengguna', href: usersIndex(), icon: Users });
  }

  if (role === 'superuser') {
    settings.items.push({ title: 'Hak Akses', href: accessIndex(), icon: ShieldCheck });
  }

  groups.push(finance);

  return [...groups, settings];
}

export type QuickAction = {
  title: string;
  href: string;
  keywords: string;
};

export const quickActions: QuickAction[] = [
  { title: 'Buat MoU baru', href: contractsCreate().url, keywords: 'kontrak dokumen tambah' },
  { title: 'Buat invoice baru', href: invoicesCreate().url, keywords: 'tagihan faktur tambah' },
];
