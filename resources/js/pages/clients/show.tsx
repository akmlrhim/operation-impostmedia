import { Head, Link, router } from '@inertiajs/react';
import { FileSignature, Pencil, Receipt, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { ClientFormModal } from '@/components/crm/client-form-modal';
import {
  ClientContractsCard,
  ClientInvoicesCard,
} from '@/components/crm/clients/client-show-documents';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useRealtime } from '@/hooks/use-realtime';
import { destroy, index, show } from '@/routes/clients';
import { create as createContract } from '@/routes/contracts';
import { create as createInvoice } from '@/routes/invoices';
import type { Client, Contract, Invoice, Option } from '@/types/crm';

type Props = {
  client: Client;
  contracts: Contract[];
  invoices: Invoice[];
  statuses: Option[];
};

function Detail({
  label,
  children,
  className,
}: {
  label: string;
  children?: ReactNode;
  className?: string;
}) {
  return (
    <div className={className}>
      <dt className="text-xs text-muted-foreground">{label}</dt>
      <dd className="text-sm">{children || '-'}</dd>
    </div>
  );
}

export default function ClientShow({ client, contracts, invoices, statuses }: Props) {
  useRealtime(
    ['clients', 'contracts', 'invoices', 'attachments'],
    ['client', 'contracts', 'invoices'],
  );

  const [confirm, confirmDialog] = useConfirm();
  const [editModal, setEditModal] = useState(false);

  return (
    <>
      <Head title={client.company_name} />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title={client.company_name}
          actions={
            <>
              <Button variant="outline" onClick={() => setEditModal(true)}>
                <Pencil className="size-4" />
                Ubah
              </Button>
              <Button variant="outline" asChild>
                <Link href={createContract({ query: { client: client.id } })}>
                  <FileSignature className="size-4" />
                  Buat MoU
                </Link>
              </Button>
              <Button variant="outline" asChild>
                <Link href={createInvoice({ query: { client: client.id } })}>
                  <Receipt className="size-4" />
                  Buat invoice
                </Link>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                aria-label="Hapus klien"
                onClick={async () => {
                  const confirmed = await confirm({
                    title: `Hapus klien ${client.company_name}?`,
                    description: 'MoU dan riwayat aktivitas klien ini ikut hilang dari daftar.',
                    confirmLabel: 'Hapus klien',
                    destructive: true,
                  });

                  if (confirmed) {
                    router.delete(destroy(client.id));
                  }
                }}
              >
                <Trash2 className="size-4" />
              </Button>
            </>
          }
        />

        <div className="grid gap-4 lg:grid-cols-[1fr_1.2fr]">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Profil klien</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                <Detail label="Kode singkat" className="sm:col-span-2">
                  {client.short_code}
                </Detail>
                <Detail label="Email">{client.email}</Detail>
                <Detail label="Telepon">{client.phone}</Detail>
                <Detail label="Alamat" className="sm:col-span-2">
                  {client.address}
                </Detail>
                <Detail label="Kota">{client.city}</Detail>
                <Detail label="Status">
                  <StatusBadge value={client.status} options={statuses} />
                </Detail>
                <Detail label="Nama PIC">{client.contact_name}</Detail>
                <Detail label="Jabatan PIC">{client.contact_position}</Detail>
                <Detail label="Catatan" className="sm:col-span-2">
                  {client.notes && <span className="whitespace-pre-line">{client.notes}</span>}
                </Detail>
              </dl>
            </CardContent>
          </Card>

          <div className="space-y-4">
            <ClientContractsCard contracts={contracts} />
            <ClientInvoicesCard invoices={invoices} />
          </div>
        </div>

        <AttachmentsCard type="clients" id={client.id} attachments={client.attachments ?? []} />
      </div>

      {editModal && (
        <ClientFormModal client={client} statuses={statuses} onClose={() => setEditModal(false)} />
      )}

      {confirmDialog}
    </>
  );
}

ClientShow.layout = ({ client }: Props) => ({
  breadcrumbs: [
    { title: 'Klien', href: index() },
    { title: client.company_name, href: show(client.id) },
  ],
});
