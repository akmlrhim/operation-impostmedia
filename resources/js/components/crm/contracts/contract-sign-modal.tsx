import { useForm } from '@inertiajs/react';
import { Field } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { ImageUpload } from '@/components/crm/image-upload';
import { Button } from '@/components/ui/button';
import { DateField } from '@/components/ui/date-field';
import { sign } from '@/routes/contracts';
import type { Contract } from '@/types/crm';

type SignFormData = {
  signature: File | null;
  signed_date: string;
};

export function ContractSignModal({
  contract,
  signatureUrl,
  onClose,
}: {
  contract: Contract;
  signatureUrl: string | null;
  onClose: () => void;
}) {
  const form = useForm<SignFormData>({
    signature: null,
    signed_date: contract.signed_date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10),
  });

  return (
    <FormModal
      title={`Tandatangani ${contract.number}`}
      size="sm"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();

        form.transform((data) => {
          const payload: Record<string, unknown> = { ...data };

          if (data.signature === null) {
            delete payload.signature;
          }

          return payload;
        });

        form.post(sign(contract.id).url, {
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
            Tandatangani
          </Button>
        </>
      }
    >
      <p className="text-sm text-muted-foreground">
        Status MoU berubah jadi Ditandatangani dan siap ditagihkan.
      </p>

      <Field label="Tanggal tanda tangan" htmlFor="signed_date" error={form.errors.signed_date}>
        <DateField
          id="signed_date"
          value={form.data.signed_date}
          clearable={false}
          onChange={(value) => form.setData('signed_date', value)}
        />
      </Field>

      <Field
        label="Tanda tangan klien"
        hint="Tersimpan pada MoU ini dan tercetak di blok tanda tangan pihak pertama."
        error={form.errors.signature}
      >
        <ImageUpload
          id="contract_signature"
          currentUrl={signatureUrl}
          file={form.data.signature}
          removed={false}
          emptyLabel="Boleh dikosongkan kalau tanda tangan dibubuhkan basah di atas kertas."
          onSelect={(file) => form.setData('signature', file)}
          onRemove={() => form.setData('signature', null)}
        />
      </Field>
    </FormModal>
  );
}
