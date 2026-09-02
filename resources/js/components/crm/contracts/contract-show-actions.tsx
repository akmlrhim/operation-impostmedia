import { Link, router } from '@inertiajs/react';
import { Download, FilePenLine, FileText, Pencil, Printer, Receipt, Trash2 } from 'lucide-react';
import type { ConfirmFn } from '@/components/crm/confirm-dialog';
import { Button } from '@/components/ui/button';
import {
  document as documentRoute,
  edit,
  finalize,
  invoice as makeInvoice,
  pdf,
  print,
} from '@/routes/contracts';
import type { Contract } from '@/types/crm';

export function ContractShowActions({
  contract,
  canInvoice,
  confirm,
  onDelete,
}: {
  contract: Contract;
  canInvoice: boolean;
  confirm: ConfirmFn;
  onDelete: () => void;
}) {
  return (
    <>
      <Button variant="outline" asChild>
        <Link href={edit(contract.id)} aria-label="Ubah MoU">
          <Pencil className="size-4" />
          <span className="hidden sm:inline">Ubah</span>
        </Link>
      </Button>

      <Button
        variant="outline"
        aria-label="Generate dokumen"
        onClick={() => router.post(finalize(contract.id), {}, { preserveScroll: true })}
      >
        <FileText className="size-4" />
        <span className="hidden sm:inline">Generate dokumen</span>
      </Button>

      <Button variant="outline" asChild>
        <Link href={documentRoute(contract.id)} aria-label="Sunting isi dokumen">
          <FilePenLine className="size-4" />
          <span className="hidden sm:inline">Sunting dokumen</span>
        </Link>
      </Button>

      <Button variant="outline" asChild>
        <a href={print(contract.id).url} target="_blank" rel="noreferrer" aria-label="Cetak MoU">
          <Printer className="size-4" />
          <span className="hidden sm:inline">Cetak</span>
        </a>
      </Button>

      <Button variant="outline" asChild>
        <a href={pdf(contract.id).url} aria-label="Unduh PDF">
          <Download className="size-4" />
          <span className="hidden sm:inline">Unduh PDF</span>
        </a>
      </Button>

      {canInvoice && (
        <Button
          aria-label="Terbitkan invoice"
          onClick={() => router.post(makeInvoice(contract.id))}
        >
          <Receipt className="size-4" />
          <span className="hidden sm:inline">Terbitkan invoice</span>
        </Button>
      )}

      <Button
        variant="ghost"
        size="icon"
        aria-label="Hapus MoU"
        onClick={async () => {
          const confirmed = await confirm({
            title: `Hapus ${contract.number}?`,
            description: 'Nomor MoU yang sudah terpakai tidak dipakai ulang.',
            confirmLabel: 'Hapus MoU',
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
