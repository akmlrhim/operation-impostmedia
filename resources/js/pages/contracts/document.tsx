import { Head, router, useForm } from '@inertiajs/react';
import { RotateCcw, Save } from 'lucide-react';
import { useCallback, useState } from 'react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { DocumentEditor } from '@/components/crm/contracts/document-editor';
import type { DocumentEditorHandle } from '@/components/crm/contracts/document-editor';
import { DocumentToolbar } from '@/components/crm/contracts/document-toolbar';
import { PageHeader } from '@/components/crm/page-header';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/contracts';
import { document as documentRoute } from '@/routes/contracts';
import { reset as resetDocument, update as saveDocument } from '@/routes/contracts/document';

type Props = {
  contract: { id: number; number: string; title: string };
  page: string;
  edited: boolean;
};

export default function ContractDocument({ contract, page, edited }: Props) {
  const [editor, setEditor] = useState<DocumentEditorHandle | null>(null);
  const [version, setVersion] = useState(0);
  const [dirty, setDirty] = useState(false);
  const [confirm, confirmDialog] = useConfirm();

  const form = useForm<{ body: string }>({ body: '' });

  const onReady = useCallback((handle: DocumentEditorHandle) => setEditor(handle), []);
  const redraw = useCallback(() => setVersion((n) => n + 1), []);

  const onDirty = useCallback(() => {
    setDirty(true);
    redraw();
  }, [redraw]);

  function save() {
    const body = editor?.html();

    if (body === undefined) {
      return;
    }

    form.transform(() => ({ body }));
    form.put(saveDocument(contract.id).url, {
      preserveScroll: true,
      onSuccess: () => setDirty(false),
    });
  }

  async function revert() {
    if (
      await confirm({
        title: 'Kembalikan ke template?',
        description:
          'Seluruh suntingan pada dokumen ini dibuang, dan isinya disusun ulang dari template beserta data MoU terbaru.',
        confirmLabel: 'Kembalikan',
        destructive: true,
      })
    ) {
      router.delete(resetDocument(contract.id));
    }
  }

  return (
    <>
      <Head title={`Dokumen ${contract.number}`} />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title={`Dokumen ${contract.number}`}
          backHref={show(contract.id).url}
          actions={
            <div className="flex items-center gap-2">
              {edited && (
                <Button type="button" variant="outline" size="sm" onClick={revert}>
                  <RotateCcw className="size-4" />
                  Kembalikan ke template
                </Button>
              )}

              <Button type="button" size="sm" disabled={!dirty || form.processing} onClick={save}>
                <Save className="size-4" />
                Simpan dokumen
              </Button>
            </div>
          }
        />

        <p className="text-sm text-muted-foreground">
          {edited
            ? 'Dokumen ini sudah disunting sendiri, jadi tidak lagi mengikuti susunan template. Perubahan pada template atau data MoU tidak masuk ke sini sampai dikembalikan.'
            : 'Ketik langsung di halaman untuk menyunting isinya. Begitu disimpan, dokumen ini lepas dari susunan template dan yang tercetak adalah apa yang terlihat di sini.'}
        </p>

        <DocumentToolbar editor={editor} version={version} />

        <DocumentEditor page={page} onReady={onReady} onDirty={onDirty} onSelection={redraw} />
      </div>

      {confirmDialog}
    </>
  );
}

ContractDocument.layout = ({ contract }: Props) => ({
  breadcrumbs: [
    { title: 'MoU & Kontrak', href: index() },
    { title: contract.number, href: show(contract.id) },
    { title: 'Dokumen', href: documentRoute(contract.id) },
  ],
});
