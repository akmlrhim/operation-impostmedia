import { useForm } from '@inertiajs/react';
import { Field } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { ImageUpload } from '@/components/crm/image-upload';
import { Button } from '@/components/ui/button';
import { rupiah } from '@/lib/format';
import { settle } from '@/routes/invoices';
import type { Invoice } from '@/types/crm';

export function SettleInvoiceModal({
  invoice,
  onClose,
}: {
  invoice: Invoice;
  onClose: () => void;
}) {
  const form = useForm<{ proof: File | null }>({ proof: null });

  return (
    <FormModal
      title={`Lunaskan ${invoice.number}`}
      size="sm"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();

        form.transform((data) => {
          const payload: Record<string, unknown> = { ...data };

          if (data.proof === null) {
            delete payload.proof;
          }

          return payload;
        });

        form.post(settle(invoice.id).url, {
          preserveScroll: true,
          forceFormData: true,
          onSuccess: onClose,
        });
      }}
      footer={
        <>
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" disabled={form.processing}>
            Lunaskan
          </Button>
        </>
      }
    >
      <p className="text-sm text-muted-foreground">
        Sisa {rupiah(invoice.balance_due)} dicatat sebagai pembayaran transfer hari ini.
      </p>

      <Field
        label="Bukti pembayaran"
        hint="Unggah scan/foto bukti transfer. Tertaut pada riwayat pembayaran."
        error={form.errors.proof}
      >
        <ImageUpload
          id="settle_proof"
          currentUrl={null}
          file={form.data.proof}
          removed={false}
          emptyLabel="Opsional: unggah bukti transfer pembayaran."
          sizeHint="PNG atau JPG, maksimal 5 MB."
          onSelect={(file) => form.setData('proof', file)}
          onRemove={() => form.setData('proof', null)}
        />
      </Field>
    </FormModal>
  );
}
