import { Head, Link, usePage } from '@inertiajs/react';
import {
  Building2,
  ChevronRight,
  FileSignature,
  LayoutDashboard,
  Package,
  Receipt,
  Target,
  Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { index as clientsIndex } from '@/routes/clients';
import { index as contractsIndex } from '@/routes/contracts';
import { dashboard as crmDashboard } from '@/routes/crm';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as leadsIndex } from '@/routes/leads';
import { index as servicesIndex } from '@/routes/services';
import { index as usersIndex } from '@/routes/users';

type Props = {
  company: string;
};

type Module = {
  title: string;
  description: string;
  href: string;
  icon: LucideIcon;
};

const crmModules: Module[] = [
  {
    title: 'Dashboard CRM',
    description: 'Ringkasan pendapatan, piutang, dan pipeline.',
    href: crmDashboard().url,
    icon: LayoutDashboard,
  },
  {
    title: 'Leads',
    description: 'Kelola calon klien dan tahap follow up.',
    href: leadsIndex().url,
    icon: Target,
  },
  {
    title: 'Klien',
    description: 'Data perusahaan dan kontak klien.',
    href: clientsIndex().url,
    icon: Building2,
  },
  {
    title: 'MoU & Kontrak',
    description: 'Dokumen kontrak dan status penandatanganan.',
    href: contractsIndex().url,
    icon: FileSignature,
  },
  {
    title: 'Invoice',
    description: 'Tagihan, pembayaran, dan status piutang.',
    href: invoicesIndex().url,
    icon: Receipt,
  },
  {
    title: 'Layanan',
    description: 'Katalog layanan dan paket harga.',
    href: servicesIndex().url,
    icon: Package,
  },
];

const settingsModules: Module[] = [
  {
    title: 'User',
    description: 'Kelola akun dan persetujuan anggota.',
    href: usersIndex().url,
    icon: Users,
  },
];

function greeting(): string {
  const hour = new Date().getHours();

  if (hour < 11) {
    return 'Selamat pagi';
  }

  if (hour < 15) {
    return 'Selamat siang';
  }

  if (hour < 19) {
    return 'Selamat sore';
  }

  return 'Selamat malam';
}

function ModuleCard({ module: item }: { module: Module }) {
  return (
    <Link href={item.href} prefetch>
      <Card className="h-full transition-colors hover:bg-accent/50">
        <CardContent className="flex items-start gap-3">
          <span className="rounded-lg bg-primary/10 p-2 text-primary">
            <item.icon aria-hidden className="size-5" />
          </span>

          <div className="min-w-0 flex-1 space-y-0.5">
            <p className="text-sm font-medium">{item.title}</p>
            <p className="text-xs text-muted-foreground">{item.description}</p>
          </div>

          <ChevronRight aria-hidden className="mt-1 size-4 shrink-0 text-muted-foreground" />
        </CardContent>
      </Card>
    </Link>
  );
}

export default function General({ company }: Props) {
  const { auth } = usePage().props;
  const isSuperuser = auth.user?.role === 'superuser';

  return (
    <>
      <Head title="General" />

      <div className="flex flex-1 flex-col gap-8 p-4">
        <div className="space-y-1">
          <h1 className="text-2xl font-semibold tracking-tight">
            {greeting()}, {auth.user?.name}
          </h1>
          <p className="text-muted-foreground">{company}</p>
        </div>

        <div className="space-y-3">
          <h2 className="text-sm font-medium text-muted-foreground">CRM</h2>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {crmModules.map((item) => (
              <ModuleCard key={item.title} module={item} />
            ))}
          </div>
        </div>

        {isSuperuser && (
          <div className="space-y-3">
            <h2 className="text-sm font-medium text-muted-foreground">Pengaturan</h2>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              {settingsModules.map((item) => (
                <ModuleCard key={item.title} module={item} />
              ))}
            </div>
          </div>
        )}
      </div>
    </>
  );
}
