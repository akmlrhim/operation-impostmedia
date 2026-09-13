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
        Status MoU berubah jadi Ditandatangani dan siap ditagihkan. Tanda tangan klien wajib
        diunggah supaya PDF MoU memuat tanda tangan yang sudah dibubuhkan klien.
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
        required
        hint="Unggah scan/foto tanda tangan yang sudah dibubuhkan klien. Tercetak di blok CLIENT pada PDF MoU."
        error={form.errors.signature}
      >
        <ImageUpload
          id="contract_signature"
          currentUrl={signatureUrl}
          file={form.data.signature}
          removed={false}
          emptyLabel="Unggah scan atau foto tanda tangan klien yang sudah ditandatangani."
          onSelect={(file) => form.setData('signature', file)}
          onRemove={() => form.setData('signature', null)}
        />
      </Field>
    </FormModal>
  );
}
