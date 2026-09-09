import type { InertiaForm } from '@inertiajs/react';
import type { ClientFormData } from '@/components/crm/client-form-modal-types';
import { Field, FormGrid } from '@/components/crm/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export function ClientFormModalContact({ form }: { form: InertiaForm<ClientFormData> }) {
  return (
    <FormGrid className="sm:grid-cols-1">
      <Field label="Nama PIC" htmlFor="contact_name" error={form.errors.contact_name}>
        <Input
          id="contact_name"
          value={form.data.contact_name}
          onChange={(e) => form.setData('contact_name', e.target.value)}
          placeholder="Masukkan nama PIC"
        />
      </Field>

      <Field
        label="Jabatan PIC"
        htmlFor="contact_position"
        hint="Dipakai sebagai penanda tangan MoU."
        error={form.errors.contact_position}
      >
        <Input
          id="contact_position"
          value={form.data.contact_position}
          onChange={(e) => form.setData('contact_position', e.target.value)}
          placeholder="Masukkan jabatan PIC"
        />
      </Field>

      <Field label="Catatan" htmlFor="notes" error={form.errors.notes}>
        <Textarea
          id="notes"
          rows={3}
          value={form.data.notes}
          onChange={(e) => form.setData('notes', e.target.value)}
          placeholder="Masukkan catatan"
        />
      </Field>
    </FormGrid>
  );
}
