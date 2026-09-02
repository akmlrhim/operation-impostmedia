import type { InertiaForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import Heading from '@/components/heading';
import type { CompanyFormData } from '@/components/settings/company-settings-types';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export function CompanySettingsProfile({ form }: { form: InertiaForm<CompanyFormData> }) {
  return (
    <>
      <Heading
        variant="small"
        title="Profil perusahaan"
        description="Dipakai sebagai kop surat MoU dan invoice"
      />

      <FormGrid>
        <Field
          label="Nama perusahaan"
          htmlFor="name"
          required
          className="sm:col-span-2"
          error={form.errors.name}
        >
          <Input
            id="name"
            value={form.data.name}
            onChange={(e) => form.setData('name', e.target.value)}
            required
            placeholder="Masukkan nama perusahaan"
          />
        </Field>

        <Field label="Kota" htmlFor="city" error={form.errors.city}>
          <Input
            id="city"
            value={form.data.city}
            onChange={(e) => form.setData('city', e.target.value)}
            placeholder="Masukkan kota"
          />
        </Field>

        <Field label="Email" htmlFor="email" error={form.errors.email}>
          <Input
            id="email"
            type="email"
            value={form.data.email}
            onChange={(e) => form.setData('email', e.target.value)}
            placeholder="Masukkan email"
          />
        </Field>

        <Field label="Telepon" htmlFor="phone" error={form.errors.phone}>
          <Input
            id="phone"
            value={form.data.phone}
            onChange={(e) => form.setData('phone', e.target.value)}
            placeholder="Masukkan nomor telepon"
          />
        </Field>

        <Field
          label="Alamat"
          htmlFor="address"
          className="sm:col-span-2"
          error={form.errors.address}
        >
          <Textarea
            id="address"
            rows={2}
            value={form.data.address}
            onChange={(e) => form.setData('address', e.target.value)}
            placeholder="Masukkan alamat"
          />
        </Field>
      </FormGrid>
    </>
  );
}
