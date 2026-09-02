import { Download, Trash2, X } from 'lucide-react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { Button } from '@/components/ui/button';

export function BulkActionsBar({
  count,
  noun,
  exportHref,
  deleteTitle,
  deleteDescription,
  onDelete,
  onClear,
}: {
  count: number;
  noun: string;
  exportHref: string;
  deleteTitle: string;
  deleteDescription: string;
  onDelete: () => void;
  onClear: () => void;
}) {
  const [confirm, confirmDialog] = useConfirm();

  if (count === 0) {
    return null;
  }

  return (
    <div className="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/40 px-3 py-2 text-sm">
      <span className="font-medium">
        {count} {noun} dipilih
      </span>

      <div className="ml-auto flex items-center gap-2">
        <Button type="button" variant="outline" size="sm" asChild>
          <a href={exportHref}>
            <Download className="size-4" />
            Export CSV
          </a>
        </Button>

        <Button
          type="button"
          variant="outline"
          size="sm"
          className="text-destructive hover:text-destructive"
          onClick={async () => {
            const confirmed = await confirm({
              title: deleteTitle,
              description: deleteDescription,
              confirmLabel: 'Hapus',
              destructive: true,
            });

            if (confirmed) {
              onDelete();
            }
          }}
        >
          <Trash2 className="size-4" />
          Hapus
        </Button>

        <Button type="button" variant="ghost" size="sm" onClick={onClear}>
          <X className="size-4" />
          Batal
        </Button>
      </div>

      {confirmDialog}
    </div>
  );
}
