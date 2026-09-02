import type { InertiaForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import type { LeadFormData } from '@/components/crm/lead-form-modal-types';
import { DateField, DateTimeField } from '@/components/ui/date-field';
import { Input } from '@/components/ui/input';
import { MoneyInput } from '@/components/ui/money-input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Option, UserRef } from '@/types/crm';

export function LeadFormModalFields({
  form,
  stages,
  users,
  priorities,
  sources,
  statuses,
}: {
  form: InertiaForm<LeadFormData>;
  stages: { id: number; name: string }[];
  users: UserRef[];
  priorities: Option[];
  sources: Option[];
  statuses: Option[];
}) {
  return (
    <FormGrid className="lg:grid-cols-3">
      <Field
        label="Nama perusahaan"
        htmlFor="company_name"
        required
        error={form.errors.company_name}
      >
        <Input
          id="company_name"
          value={form.data.company_name}
          onChange={(e) => form.setData('company_name', e.target.value)}
          placeholder="Masukkan nama perusahaan"
          required
        />
      </Field>

      <Field label="Nama kontak" htmlFor="contact_name" required error={form.errors.contact_name}>
        <Input
          id="contact_name"
          value={form.data.contact_name}
          onChange={(e) => form.setData('contact_name', e.target.value)}
          placeholder="Masukkan nama kontak"
          required
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
        label="Estimasi nilai"
        htmlFor="estimated_value"
        required
        error={form.errors.estimated_value}
      >
        <MoneyInput
          id="estimated_value"
          value={form.data.estimated_value}
          onChange={(value) => form.setData('estimated_value', value)}
          placeholder="Masukkan estimasi nilai"
          required
        />
      </Field>

      <Field label="Sumber" error={form.errors.source}>
        <Select
          value={form.data.source || 'none'}
          onValueChange={(value) => form.setData('source', value === 'none' ? '' : value)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="none">Belum ditentukan</SelectItem>
            {sources.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>

      <Field label="Stage" error={form.errors.lead_stage_id}>
        <Select
          value={form.data.lead_stage_id}
          onValueChange={(value) => form.setData('lead_stage_id', value)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {stages.map((stage) => (
              <SelectItem key={stage.id} value={String(stage.id)}>
                {stage.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>

      <Field label="Prioritas" error={form.errors.priority}>
        <Select
          value={form.data.priority}
          onValueChange={(value) => form.setData('priority', value)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {priorities.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>

      <Field label="Sales owner" error={form.errors.owner_id}>
        <Select
          value={form.data.owner_id || 'none'}
          onValueChange={(value) => form.setData('owner_id', value === 'none' ? '' : value)}
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

      <Field
        label="Target closing"
        htmlFor="expected_close_date"
        error={form.errors.expected_close_date}
      >
        <DateField
          id="expected_close_date"
          value={form.data.expected_close_date}
          onChange={(value) => form.setData('expected_close_date', value)}
        />
      </Field>

      <Field
        label="Follow up berikutnya"
        htmlFor="next_follow_up_at"
        error={form.errors.next_follow_up_at}
      >
        <DateTimeField
          id="next_follow_up_at"
          value={form.data.next_follow_up_at}
          onChange={(value) => form.setData('next_follow_up_at', value)}
        />
      </Field>

      {form.data.status === 'lost' && (
        <Field
          label="Alasan gagal"
          htmlFor="lost_reason"
          required
          className="sm:col-span-2 lg:col-span-3"
          error={form.errors.lost_reason}
        >
          <Input
            id="lost_reason"
            value={form.data.lost_reason}
            onChange={(e) => form.setData('lost_reason', e.target.value)}
            required
            placeholder="Masukkan alasan gagal"
          />
        </Field>
      )}

      <Field
        label="Catatan"
        htmlFor="notes"
        className="sm:col-span-2 lg:col-span-3"
        error={form.errors.notes}
      >
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
