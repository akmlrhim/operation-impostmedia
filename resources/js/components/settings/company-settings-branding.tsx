import type { InertiaForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import { ImageUpload } from '@/components/crm/image-upload';
import Heading from '@/components/heading';
import type { CompanyFormData } from '@/components/settings/company-settings-types';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';

export function CompanySettingsBranding({
  form,
  logoUrl,
  signatureUrl,
  stampUrl,
}: {
  form: InertiaForm<CompanyFormData>;
  logoUrl: string | null;
  signatureUrl: string | null;
  stampUrl: string | null;
}) {
  return (
    <>
      <Separator />

      <Heading
        variant="small"
        title="Logo"
        description="Dicetak sebagai kop surat MoU dan invoice"
      />

      <Field label="Berkas logo" error={form.errors.logo}>
        <ImageUpload
          id="logo"
          currentUrl={logoUrl}
          file={form.data.logo}
          removed={form.data.remove_logo}
          emptyLabel="Tanpa logo, kop surat hanya menampilkan nama perusahaan."
          onSelect={(file) => {
            form.setData('logo', file);
            form.setData('remove_logo', false);
          }}
          onRemove={() => form.setData('remove_logo', true)}
        />
      </Field>

      <Separator />

      <Heading
        variant="small"
        title="Penanda tangan"
        description="Muncul sebagai Pihak Pertama pada MoU"
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

      <FormGrid>
        <Field label="Nama" htmlFor="signatory_name" error={form.errors.signatory_name}>
          <Input
            id="signatory_name"
            value={form.data.signatory_name}
            onChange={(e) => form.setData('signatory_name', e.target.value)}
            placeholder="Masukkan nama penanda tangan"
          />
        </Field>

        <Field label="Jabatan" htmlFor="signatory_position" error={form.errors.signatory_position}>
          <Input
            id="signatory_position"
            value={form.data.signatory_position}
            onChange={(e) => form.setData('signatory_position', e.target.value)}
            placeholder="Masukkan jabatan penanda tangan"
          />
        </Field>
      </FormGrid>
    </>
  );
}
