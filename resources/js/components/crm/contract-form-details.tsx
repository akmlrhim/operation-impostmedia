import type { InertiaForm } from '@inertiajs/react';
import { AssigneeField } from '@/components/crm/assignee-field';
import type { ContractFormData } from '@/components/crm/contract-form-types';
import { Field, FormGrid } from '@/components/crm/field';
import { DateField } from '@/components/ui/date-field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { Client, Option, UserOption } from '@/types/crm';

export function ContractFormDetails({
  form,
  isEdit,
  clients,
  lockedClientName,
  types,
  statuses,
  billingCycles,
  users,
  onNumberEdited,
  onSelectSignedDate,
}: {
  form: InertiaForm<ContractFormData>;
  isEdit: boolean;
  clients: Pick<Client, 'id' | 'company_name'>[];
  lockedClientName?: string;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
  users: UserOption[];
  onNumberEdited: () => void;
  onSelectSignedDate: (value: string) => void;
}) {
  return (
    <FormGrid className="lg:grid-cols-3">
      <Field
        label="Nomor MoU"
        htmlFor="number"
        required
        hint="Terisi otomatis dari urutan berjalan. Boleh diubah bila penomorannya perlu menyesuaikan."
        error={form.errors.number}
      >
        <Input
          id="number"
          value={form.data.number}
          onChange={(e) => {
            onNumberEdited();
            form.setData('number', e.target.value);
          }}
          required
          placeholder="Masukkan nomor MoU"
        />
      </Field>

      <Field label="Klien" required error={form.errors.client_id}>
        {lockedClientName ? (
          <Input value={lockedClientName} disabled />
        ) : (
          <Select
            value={form.data.client_id}
            onValueChange={(value) => form.setData('client_id', value)}
          >
            <SelectTrigger>
              <SelectValue placeholder="Pilih klien" />
            </SelectTrigger>
            <SelectContent>
              {clients.map((client) => (
                <SelectItem key={client.id} value={String(client.id)}>
                  {client.company_name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        )}
      </Field>

      <Field label="Jenis dokumen" error={form.errors.type}>
        <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {types.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
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
        label="Judul pekerjaan"
        htmlFor="title"
        required
        className="lg:col-span-3"
        error={form.errors.title}
      >
        <Input
          id="title"
          value={form.data.title}
          onChange={(e) => form.setData('title', e.target.value)}
          placeholder="Masukkan judul pekerjaan"
          required
        />
      </Field>

      <Field label="Mulai" htmlFor="start_date" error={form.errors.start_date}>
        <DateField
          id="start_date"
          value={form.data.start_date}
          onChange={(value) => form.setData('start_date', value)}
        />
      </Field>

      <Field label="Berakhir" htmlFor="end_date" error={form.errors.end_date}>
        <DateField
          id="end_date"
          value={form.data.end_date}
          min={form.data.start_date || undefined}
          onChange={(value) => form.setData('end_date', value)}
        />
      </Field>

      <Field
        label="Tanggal MoU"
        htmlFor="signed_date"
        required
        hint={isEdit ? undefined : 'Tanggalnya ikut tercetak di nomor MoU.'}
        error={form.errors.signed_date}
      >
        <DateField
          id="signed_date"
          value={form.data.signed_date}
          required
          clearable={false}
          onChange={onSelectSignedDate}
        />
      </Field>

      <Field
        label="Tempat penandatanganan"
        htmlFor="signing_place"
        error={form.errors.signing_place}
      >
        <Input
          id="signing_place"
          value={form.data.signing_place}
          onChange={(e) => form.setData('signing_place', e.target.value)}
          placeholder="Masukkan tempat penandatanganan"
        />
      </Field>

      <Field label="Siklus penagihan" error={form.errors.billing_cycle}>
        <Select
          value={form.data.billing_cycle}
          onValueChange={(value) => form.setData('billing_cycle', value)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {billingCycles.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>

      <Field
        label="Invoice berikutnya"
        htmlFor="next_invoice_date"
        hint="Diisi untuk kontrak retainer."
        error={form.errors.next_invoice_date}
      >
        <DateField
          id="next_invoice_date"
          value={form.data.next_invoice_date}
          onChange={(value) => form.setData('next_invoice_date', value)}
        />
      </Field>
      <AssigneeField
        value={form.data.assigned_to_ids}
        users={users}
        error={form.errors.assigned_to_ids}
        onChange={(value) => form.setData('assigned_to_ids', value)}
      />
    </FormGrid>
  );
}
