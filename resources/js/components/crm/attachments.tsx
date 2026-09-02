import { router, useForm } from '@inertiajs/react';
import {
  Download,
  FileText,
  Image as ImageIcon,
  Paperclip,
  Table2,
  Trash2,
  Upload,
} from 'lucide-react';
import { useRef } from 'react';

import { useConfirm } from '@/components/crm/confirm-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { fileSize, formatDate } from '@/lib/format';
import { destroy, download, store } from '@/routes/attachments';
import type { AttachmentItem, AttachmentOwner } from '@/types/crm';

function iconFor(mime: string | null) {
  if (mime === null) {
    return Paperclip;
  }

  if (mime.startsWith('image/')) {
    return ImageIcon;
  }

  if (mime.includes('spreadsheet') || mime.includes('excel') || mime === 'text/csv') {
    return Table2;
  }

  return FileText;
}

export function AttachmentsCard({
  type,
  id,
  attachments,
  description = 'Scan dokumen, bukti transfer, atau brief dari klien.',
}: {
  type: AttachmentOwner;
  id: number;
  attachments: AttachmentItem[];
  description?: string;
}) {
  const form = useForm<{ file: File | null }>({ file: null });
  const inputRef = useRef<HTMLInputElement>(null);
  const [confirm, confirmDialog] = useConfirm();

  function upload(file: File | null) {
    if (file === null) {
      return;
    }

    form.setData('file', file);

    form.submit(store({ type, id }), {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        form.reset();

        if (inputRef.current !== null) {
          inputRef.current.value = '';
        }
      },
    });
  }

  async function remove(attachment: AttachmentItem) {
    const confirmed = await confirm({
      title: `Hapus lampiran ${attachment.name}?`,
      description: 'Berkasnya ikut terhapus dari penyimpanan.',
      confirmLabel: 'Hapus lampiran',
      destructive: true,
    });

    if (confirmed) {
      router.delete(destroy(attachment.id), { preserveScroll: true });
    }
  }

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-3 space-y-0">
        <div className="grid gap-0.5">
          <CardTitle className="text-base">Lampiran</CardTitle>
          <p className="text-xs text-muted-foreground">{description}</p>
        </div>

        <Button type="button" variant="outline" size="sm" disabled={form.processing} asChild>
          <label htmlFor={`attachment-${type}-${id}`} className="cursor-pointer">
            <Upload className="size-3.5" />
            {form.processing ? 'Mengunggah…' : 'Unggah'}
          </label>
        </Button>
      </CardHeader>

      <CardContent className="space-y-2">
        <input
          ref={inputRef}
          id={`attachment-${type}-${id}`}
          type="file"
          className="sr-only"
          onChange={(event) => upload(event.target.files?.[0] ?? null)}
        />

        <InputError message={form.errors.file} />

        {attachments.length === 0 && (
          <p className="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
            Belum ada lampiran.
          </p>
        )}

        {attachments.map((attachment) => {
          const Icon = iconFor(attachment.mime_type);

          return (
            <div
              key={attachment.id}
              className="flex items-center gap-3 rounded-lg border p-3 text-sm transition-colors hover:bg-accent/50"
            >
              <Icon className="size-4 shrink-0 text-muted-foreground" aria-hidden />

              <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{attachment.name}</p>
                <p className="truncate text-xs text-muted-foreground">
                  {fileSize(attachment.size)} · {formatDate(attachment.created_at)}
                  {attachment.uploader && ` · ${attachment.uploader.name}`}
                </p>
              </div>

              <Button variant="ghost" size="icon" className="size-8 shrink-0" asChild>
                <a href={download(attachment.id).url} aria-label={`Unduh ${attachment.name}`}>
                  <Download className="size-4" />
                </a>
              </Button>

              <Button
                variant="ghost"
                size="icon"
                className="size-8 shrink-0 text-muted-foreground hover:text-destructive"
                aria-label={`Hapus ${attachment.name}`}
                onClick={() => remove(attachment)}
              >
                <Trash2 className="size-4" />
              </Button>
            </div>
          );
        })}
      </CardContent>

      {confirmDialog}
    </Card>
  );
}
