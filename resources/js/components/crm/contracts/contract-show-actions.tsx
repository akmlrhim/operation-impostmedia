import { Link, router } from '@inertiajs/react';
import {
  Download,
  FilePenLine,
  FileText,
  MoreHorizontal,
  Pencil,
  PenLine,
  Trash2,
} from 'lucide-react';
import type { ConfirmFn } from '@/components/crm/confirm-dialog';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCan } from '@/lib/use-can';
import { document as documentRoute, edit, finalize, pdf } from '@/routes/contracts';
import type { Contract } from '@/types/crm';

const SIGNABLE = ['draft', 'review', 'sent'];

export function ContractShowActions({
  contract,
  confirm,
  onSign,
  onDelete,
}: {
  contract: Contract;
  confirm: ConfirmFn;
  onSign: () => void;
  onDelete: () => void;
}) {
  const can = useCan();

  return (
    <>
      <Button variant="outline" asChild>
        <Link href={edit(contract.id)} aria-label="Ubah MoU">
          <Pencil className="size-4" />
          <span className="hidden sm:inline">Ubah</span>
        </Link>
      </Button>

      <Button variant="outline" asChild>
        <Link href={documentRoute(contract.id)} aria-label="Sunting isi dokumen">
          <FilePenLine className="size-4" />
          <span className="hidden sm:inline">Sunting dokumen</span>
        </Link>
      </Button>

      {can['approve-documents'] && SIGNABLE.includes(contract.status) && (
        <Button aria-label="Tandatangani MoU" onClick={onSign}>
          <PenLine className="size-4" />
          <span className="hidden sm:inline">Tandatangani</span>
        </Button>
      )}

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline" size="icon" aria-label="Aksi dokumen lainnya">
            <MoreHorizontal className="size-4" />
          </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
          {can['approve-documents'] && (
            <DropdownMenuItem
              onSelect={() => router.post(finalize(contract.id), {}, { preserveScroll: true })}
            >
              <FileText className="size-4" />
              Generate dokumen
            </DropdownMenuItem>
          )}

          <DropdownMenuItem asChild>
            <a href={pdf(contract.id).url}>
              <Download className="size-4" />
              Unduh PDF
            </a>
          </DropdownMenuItem>

          {can['manage-records'] && (
            <>
              <DropdownMenuSeparator />

              <DropdownMenuItem
                variant="destructive"
                onSelect={async () => {
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
                Hapus MoU
              </DropdownMenuItem>
            </>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </>
  );
}
