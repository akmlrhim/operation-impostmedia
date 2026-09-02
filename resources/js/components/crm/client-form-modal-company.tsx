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
import type { Option } from '@/types/crm';

export function ClientFormModalCompany({
  form,
  isEdit,
  statuses,
  onChangeCompanyName,
  onShortCodeEdited,
}: {
  form: InertiaForm<ClientFormData>;
  isEdit: boolean;
  statuses: Option[];
  onChangeCompanyName: (value: string) => void;
  onShortCodeEdited: () => void;
}) {
  return (
    <FormGrid className="sm:grid-cols-1">
      <Field
        label="Nama perusahaan"
        htmlFor="company_name"
        required
        error={form.errors.company_name}
      >
        <Input
          id="company_name"
          value={form.data.company_name}
          onChange={(e) => onChangeCompanyName(e.target.value)}
          required
          placeholder="Masukkan nama perusahaan"
        />
      </Field>

      <Field
        label="Kode singkat"
        htmlFor="short_code"
        hint={
          isEdit
            ? 'Dipakai di nomor invoice, mis. IM-WOS/001/05/26. Mengubahnya hanya berlaku untuk invoice berikutnya.'
            : 'Mengikuti nama perusahaan. Boleh diubah sendiri bila perlu.'
        }
        error={form.errors.short_code}
      >
        <Input
          id="short_code"
          value={form.data.short_code}
          onChange={(e) => {
            onShortCodeEdited();
            form.setData('short_code', e.target.value.toUpperCase());
          }}
          className="uppercase"
          maxLength={20}
          placeholder="Terisi otomatis"
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

      <Field label="Alamat" htmlFor="address" error={form.errors.address}>
        <Textarea
          id="address"
          rows={2}
          value={form.data.address}
          onChange={(e) => form.setData('address', e.target.value)}
          placeholder="Masukkan alamat"
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

      <Field label="Status" error={form.errors.status}>
        <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {statuses.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>
    </FormGrid>
  );
}
