import type { InertiaForm } from '@inertiajs/react';
import { Field } from '@/components/crm/field';
import { ImageUpload } from '@/components/crm/image-upload';
import Heading from '@/components/heading';
import type { CompanyFormData } from '@/components/settings/company-settings-types';
import { Separator } from '@/components/ui/separator';

export function CompanySettingsBranding({
  form,
  signatureUrl,
  stampUrl,
}: {
  form: InertiaForm<CompanyFormData>;
  signatureUrl: string | null;
  stampUrl: string | null;
}) {
  return (
    <>
      <Separator />

      <Heading
        variant="small"
        title="Tanda tangan dan meterai"
        description="Ditempel pada blok tanda tangan MoU"
      />

      <Field label="Spesimen tanda tangan" error={form.errors.signature}>
        <ImageUpload
          id="signature"
          currentUrl={signatureUrl}
          file={form.data.signature}
          removed={form.data.remove_signature}
          emptyLabel="Tanpa spesimen, dokumen hanya menyisakan ruang tanda tangan basah."
          onSelect={(file) => {
            form.setData('signature', file);
            form.setData('remove_signature', false);
          }}
          onRemove={() => form.setData('remove_signature', true)}
        />
      </Field>

      <Field label="Meterai" error={form.errors.stamp}>
        <ImageUpload
          id="stamp"
          currentUrl={stampUrl}
          file={form.data.stamp}
          removed={form.data.remove_stamp}
          emptyLabel="Tanpa meterai, blok tanda tangan menyisakan ruang untuk ditempel manual."
          onSelect={(file) => {
            form.setData('stamp', file);
            form.setData('remove_stamp', false);
          }}
          onRemove={() => form.setData('remove_stamp', true)}
        />
      </Field>
    </>
  );
}
