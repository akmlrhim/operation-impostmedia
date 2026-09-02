import type { InertiaForm } from '@inertiajs/react';
import type { ClientFormData } from '@/components/crm/client-form-modal-types';
import { Field, FormGrid } from '@/components/crm/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { UserRef } from '@/types/crm';

export function ClientFormModalContact({
  form,
  users,
}: {
  form: InertiaForm<ClientFormData>;
  users: UserRef[];
}) {
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

      <Field label="Email PIC" htmlFor="contact_email" error={form.errors.contact_email}>
        <Input
          id="contact_email"
          type="email"
          value={form.data.contact_email}
          onChange={(e) => form.setData('contact_email', e.target.value)}
          placeholder="Masukkan email PIC"
        />
      </Field>

      <Field label="Telepon PIC" htmlFor="contact_phone" error={form.errors.contact_phone}>
        <Input
          id="contact_phone"
          value={form.data.contact_phone}
          onChange={(e) => form.setData('contact_phone', e.target.value)}
          placeholder="Masukkan telepon PIC"
        />
      </Field>

      <Field label="Account Manager" error={form.errors.account_manager_id}>
        <Select
          value={form.data.account_manager_id || 'none'}
          onValueChange={(value) =>
            form.setData('account_manager_id', value === 'none' ? '' : value)
          }
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="none">Belum ditentukan</SelectItem>
            {users.map((user) => (
              <SelectItem key={user.id} value={String(user.id)}>
                {user.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
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
