import type { InertiaForm } from '@inertiajs/react';
import { AssigneeField } from '@/components/crm/assignee-field';
import { Field, FormGrid } from '@/components/crm/field';
import type { LeadFormData } from '@/components/crm/lead-form-modal-types';
import { PicSelect } from '@/components/crm/pic-select';
import { ServicePackageMultiCombobox } from '@/components/crm/service-package-multi-combobox';
import { DateField } from '@/components/ui/date-field';
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
import { formatDate, rupiah } from '@/lib/format';
import type { LeadCard, Option, ServiceOption, UserOption } from '@/types/crm';

function masterTotal(services: ServiceOption[], packageIds: number[]): number | null {
  if (packageIds.length === 0) {
    return null;
  }

  const catalog = services.flatMap((service) => service.packages);

  return packageIds.reduce((sum, id) => {
    const found = catalog.find((servicePackage) => servicePackage.id === id);

    return sum + (found ? Number(found.price) : 0);
  }, 0);
}

function lastInvoiceLabel(lead?: LeadCard) {
  if (!lead?.last_invoice) {
    return 'Belum ada invoice';
  }

  const { number, issue_date: issueDate } = lead.last_invoice;

  return issueDate ? `${number} · ${formatDate(issueDate)}` : number;
}

export function LeadFormModalFields({
  form,
  lead,
  stages,
  sources,
  statuses,
  temperatures,
  services,
  users,
}: {
  form: InertiaForm<LeadFormData>;
  lead?: LeadCard;
  stages: { id: number; name: string }[];
  sources: Option[];
  statuses: Option[];
  temperatures: Option[];
  services: ServiceOption[];
  users: UserOption[];
}) {
  const masterPrice = masterTotal(services, form.data.service_package_ids);

  return (
    <FormGrid className="lg:grid-cols-4">
      <Field label="Tanggal masuk" htmlFor="date_in" required error={form.errors.date_in}>
        <DateField
          id="date_in"
          value={form.data.date_in}
          onChange={(value) => form.setData('date_in', value)}
        />
      </Field>

      <Field label="Nama klien" htmlFor="company_name" required error={form.errors.company_name}>
        <Input
          id="company_name"
          value={form.data.company_name}
          onChange={(e) => form.setData('company_name', e.target.value)}
          placeholder="Masukkan nama klien"
          required
        />
      </Field>

      <Field label="Industri" htmlFor="industry" error={form.errors.industry}>
        <Input
          id="industry"
          value={form.data.industry}
          onChange={(e) => form.setData('industry', e.target.value)}
          placeholder="Masukkan bidang industri"
        />
      </Field>

      <Field
        label="Posisi loker"
        htmlFor="vacancy_position"
        hint="Posisi yang dibuka di lowongan kerja, kalau lead ini didapat dari loker."
        error={form.errors.vacancy_position}
      >
        <Input
          id="vacancy_position"
          value={form.data.vacancy_position}
          onChange={(e) => form.setData('vacancy_position', e.target.value)}
          placeholder="Misal: Graphic Designer"
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

      <Field label="Telepon" htmlFor="phone" error={form.errors.phone}>
        <Input
          id="phone"
          value={form.data.phone}
          onChange={(e) => form.setData('phone', e.target.value)}
          placeholder="Masukkan nomor telepon"
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

      <Field label="Asal daerah" htmlFor="region" error={form.errors.region}>
        <Input
          id="region"
          value={form.data.region}
          onChange={(e) => form.setData('region', e.target.value)}
          placeholder="Masukkan asal daerah"
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

      <Field label="PIC" htmlFor="pic" error={form.errors.pic}>
        <PicSelect
          id="pic"
          value={form.data.pic}
          onChange={(value) => form.setData('pic', value)}
        />
      </Field>

      <AssigneeField
        value={form.data.assigned_to_ids}
        users={users}
        error={form.errors.assigned_to_ids}
        onChange={(value) => form.setData('assigned_to_ids', value)}
      />

      <Field
        label="Layanan dibutuhkan"
        hint="Boleh pilih lebih dari satu. Estimasi nilai terisi dari jumlah harga paket di data master layanan."
        className="sm:col-span-2"
        error={form.errors.service_package_ids}
      >
        <ServicePackageMultiCombobox
          services={services}
          value={form.data.service_package_ids}
          onChange={(packageIds) => {
            const total = masterTotal(services, packageIds);

            form.setData({
              ...form.data,
              service_package_ids: packageIds,
              estimated_value: total === null ? form.data.estimated_value : String(total),
            });
          }}
          ariaLabel="Pilih layanan yang dibutuhkan"
          placeholder="Belum ditentukan"
        />
      </Field>

      <Field label="Invoice terakhir">
        <p className="flex h-9 items-center text-sm text-muted-foreground">
          {lastInvoiceLabel(lead)}
        </p>
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

        {masterPrice !== null && masterPrice !== Number(form.data.estimated_value) && (
          <p className="mt-1 text-xs text-muted-foreground">
            Jumlah harga master {rupiah(masterPrice)}.{' '}
            <button
              type="button"
              className="cursor-pointer underline underline-offset-2 hover:text-foreground"
              onClick={() => form.setData('estimated_value', String(masterPrice))}
            >
              Pakai harga master
            </button>
          </p>
        )}
      </Field>

      <Field
        label="Kontak terakhir"
        htmlFor="last_contact_date"
        error={form.errors.last_contact_date}
      >
        <DateField
          id="last_contact_date"
          value={form.data.last_contact_date}
          onChange={(value) => form.setData('last_contact_date', value)}
        />
      </Field>

      <Field
        label="Tanggal aksi berikutnya"
        htmlFor="next_action_date"
        error={form.errors.next_action_date}
      >
        <DateField
          id="next_action_date"
          value={form.data.next_action_date}
          onChange={(value) => form.setData('next_action_date', value)}
        />
      </Field>

      <Field label="Aksi berikutnya" htmlFor="next_action" error={form.errors.next_action}>
        <Input
          id="next_action"
          value={form.data.next_action}
          onChange={(e) => form.setData('next_action', e.target.value)}
          placeholder="Misal: kirim penawaran"
        />
      </Field>

      <Field label="Tanggal meeting" htmlFor="meeting_date" error={form.errors.meeting_date}>
        <DateField
          id="meeting_date"
          value={form.data.meeting_date}
          onChange={(value) => form.setData('meeting_date', value)}
        />
      </Field>

      <Field
        label="Temperature"
        hint="Cold: baru masuk. Warm: sudah merespons. Hot: siap deal."
        error={form.errors.temperature}
      >
        <Select
          value={form.data.temperature}
          onValueChange={(value) => form.setData('temperature', value)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {temperatures.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>

      <Field label="Status deal" error={form.errors.status}>
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
        label="Link folder"
        htmlFor="folder_url"
        className="sm:col-span-2 lg:col-span-2"
        error={form.errors.folder_url}
      >
        <Input
          id="folder_url"
          type="url"
          value={form.data.folder_url}
          onChange={(e) => form.setData('folder_url', e.target.value)}
          placeholder="https://drive.google.com/..."
        />
      </Field>

      {form.data.status === 'lost' && (
        <Field
          label="Alasan gagal"
          htmlFor="lost_reason"
          required
          className="sm:col-span-2 lg:col-span-4"
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
        className="sm:col-span-2 lg:col-span-4"
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
