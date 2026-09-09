import { Link, router } from '@inertiajs/react';
import { BadgeCheck, Ban, Download, Pencil, Plus, Send, Trash2 } from 'lucide-react';
import type { ConfirmFn } from '@/components/crm/confirm-dialog';
import { Button } from '@/components/ui/button';
import { rupiah } from '@/lib/format';
import { edit, pdf, send, settle, voidMethod as voidInvoice } from '@/routes/invoices';
import type { Invoice } from '@/types/crm';

export function InvoiceShowActions({
  invoice,
  confirm,
  onPay,
  onDelete,
}: {
  invoice: Invoice;
  confirm: ConfirmFn;
  onPay: () => void;
  onDelete: () => void;
}) {
  const balance = Number(invoice.balance_due);
  const isDraft = invoice.status === 'draft';
  const isVoid = invoice.status === 'void';

  return (
    <>
      {isDraft && (
        <>
          <Button variant="outline" asChild>
            <Link href={edit(invoice.id)} aria-label="Ubah invoice">
              <Pencil className="size-4" />
              <span className="hidden sm:inline">Ubah</span>
            </Link>
          </Button>
          <Button
            aria-label="Tandai terkirim"
            onClick={() => router.post(send(invoice.id), {}, { preserveScroll: true })}
          >
            <Send className="size-4" />
            <span className="hidden sm:inline">Tandai terkirim</span>
          </Button>
        </>
      )}

      {!isDraft && !isVoid && balance > 0 && (
        <>
          <Button variant="outline" aria-label="Catat pembayaran" onClick={onPay}>
            <Plus className="size-4" />
            <span className="hidden sm:inline">Catat pembayaran</span>
          </Button>

          <Button
            aria-label="Lunaskan invoice"
            onClick={async () => {
              const confirmed = await confirm({
                title: `Lunaskan ${invoice.number}?`,
                description: `Sisa ${rupiah(invoice.balance_due)} dicatat sebagai pembayaran transfer hari ini.`,
                confirmLabel: 'Lunaskan',
              });

              if (confirmed) {
                router.post(settle(invoice.id), {}, { preserveScroll: true });
              }
            }}
          >
            <BadgeCheck className="size-4" />
            <span className="hidden sm:inline">Lunaskan</span>
          </Button>
        </>
      )}

      <Button variant="outline" asChild>
        <a href={pdf(invoice.id).url} aria-label="Unduh PDF">
          <Download className="size-4" />
          <span className="hidden sm:inline">Unduh PDF</span>
        </a>
      </Button>

      {!isVoid && !isDraft && (
        <Button
          variant="ghost"
          size="icon"
          aria-label="Batalkan invoice"
          onClick={async () => {
            const confirmed = await confirm({
              title: `Batalkan invoice ${invoice.number}?`,
              description:
                'Invoice berhenti dihitung sebagai piutang, tapi nomornya tetap tersimpan.',
              confirmLabel: 'Batalkan invoice',
              destructive: true,
            });

            if (confirmed) {
              router.post(voidInvoice(invoice.id), {}, { preserveScroll: true });
            }
          }}
        >
          <Ban className="size-4" />
        </Button>
      )}

      <Button
        variant="ghost"
        size="icon"
        aria-label="Hapus invoice"
        onClick={async () => {
          const confirmed = await confirm({
            title: `Hapus invoice ${invoice.number}?`,
            description:
              'Pembayaran yang tercatat ikut terhapus, dan nomor invoice-nya tidak dipakai ulang.',
            confirmLabel: 'Hapus invoice',
            destructive: true,
          });

          if (confirmed) {
            onDelete();
          }
        }}
      >
        <Trash2 className="size-4" />
      </Button>
    </>
  );
}
