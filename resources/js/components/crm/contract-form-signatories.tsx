import type { InertiaForm } from '@inertiajs/react';
import type { ContractFormData } from '@/components/crm/contract-form-types';
import { Field, FormGrid } from '@/components/crm/field';
import { Input } from '@/components/ui/input';

export function ContractFormSignatories({ form }: { form: InertiaForm<ContractFormData> }) {
  return (
    <FormGrid>
      <Field
        label="Nama pihak pertama (klien)"
        htmlFor="first_party_name"
        hint="Kosongkan untuk memakai PIC klien."
        error={form.errors.first_party_name}
      >
        <Input
          id="first_party_name"
          value={form.data.first_party_name}
          onChange={(e) => form.setData('first_party_name', e.target.value)}
          placeholder="Masukkan nama pihak pertama"
        />
      </Field>

      <Field
        label="Jabatan pihak pertama (klien)"
        htmlFor="first_party_position"
        error={form.errors.first_party_position}
      >
        <Input
          id="first_party_position"
          value={form.data.first_party_position}
          onChange={(e) => form.setData('first_party_position', e.target.value)}
          placeholder="Masukkan jabatan pihak pertama"
        />
      </Field>

      <Field
        label="Nama pihak kedua (kita)"
        htmlFor="second_party_name"
        hint="Kosongkan untuk memakai penandatangan di Profil Perusahaan."
        error={form.errors.second_party_name}
      >
        <Input
          id="second_party_name"
          value={form.data.second_party_name}
          onChange={(e) => form.setData('second_party_name', e.target.value)}
          placeholder="Masukkan nama pihak kedua"
        />
      </Field>

      <Field
        label="Jabatan pihak kedua (kita)"
        htmlFor="second_party_position"
        error={form.errors.second_party_position}
      >
        <Input
          id="second_party_position"
          value={form.data.second_party_position}
          onChange={(e) => form.setData('second_party_position', e.target.value)}
          placeholder="Masukkan jabatan pihak kedua"
        />
      </Field>
    </FormGrid>
  );
}
