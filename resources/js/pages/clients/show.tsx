import { Head, Link, router } from '@inertiajs/react';
import { FileSignature, Pencil, Receipt, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { ClientFormModal } from '@/components/crm/client-form-modal';
import {
  ClientContractsCard,
  ClientInvoicesCard,
} from '@/components/crm/clients/client-show-documents';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useRealtime } from '@/hooks/use-realtime';
import { destroy, index, show } from '@/routes/clients';
import { create as createContract } from '@/routes/contracts';
import { create as createInvoice } from '@/routes/invoices';
import type { Client, Contract, Invoice, Option, UserOption } from '@/types/crm';

type Props = {
  client: Client;
  contracts: Contract[];
  invoices: Invoice[];
  statuses: Option[];
  users: UserOption[];
};

export default function ClientShow({ client, contracts, invoices, statuses, users }: Props) {
  useRealtime(
    ['clients', 'contracts', 'invoices', 'attachments'],
    ['client', 'contracts', 'invoices'],
  );

  const [confirm, confirmDialog] = useConfirm();
  const [editModal, setEditModal] = useState(false);

  return (
    <>
      <Head title={client.company_name} />

      <PageHeader
        title={client.company_name}
        backHref={index().url}
        description={client.city ?? undefined}
        meta={<StatusBadge value={client.status} options={statuses} />}
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
              size="icon-sm"
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

      <PageBody>
        <div className="space-y-3">
          <Card>
            <CardHeader>
              <CardTitle>Profil klien</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-sm">
                  <tbody className="divide-y">
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Kode singkat
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.short_code || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Email
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.email || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Telepon
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.phone || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Alamat
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.address || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Kota
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.city || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Nama PIC
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.contact_name || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Jabatan PIC
                      </th>
                      <td className="border-l border-border px-3 py-2">{client.contact_position || '-'}</td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Penanggung jawab
                      </th>
                      <td className="border-l border-border px-3 py-2">
                        {client.assignees && client.assignees.length > 0 ? (
                          <ul className="flex flex-wrap gap-1.5">
                            {client.assignees.map((user) => (
                              <li key={user.id}>
                                <span className="inline-flex items-center rounded-md border px-2 py-0.5 text-xs">
                                  {user.name}
                                </span>
                              </li>
                            ))}
                          </ul>
                        ) : (
                          '-'
                        )}
                      </td>
                    </tr>
                    <tr>
                      <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                        Catatan
                      </th>
                      <td className="border-l border-border px-3 py-2 whitespace-pre-line">
                        {client.notes || '-'}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          <ClientContractsCard contracts={contracts} />
          <ClientInvoicesCard invoices={invoices} />
          <AttachmentsCard type="clients" id={client.id} attachments={client.attachments ?? []} />
          </div>
      </PageBody>

      {editModal && (
        <ClientFormModal
          client={client}
          statuses={statuses}
          users={users}
          onClose={() => setEditModal(false)}
        />
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
