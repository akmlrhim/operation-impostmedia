import { Link } from '@inertiajs/react';
import type { InertiaForm } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { AssigneeField } from '@/components/crm/assignee-field';
import { Field, FormGrid } from '@/components/crm/field';
import type { InvoiceFormData } from '@/components/crm/invoice-form-types';
import { DateField } from '@/components/ui/date-field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { show as showContract } from '@/routes/contracts';
import type { Client, ContractOption, Option, UserOption } from '@/types/crm';

export function InvoiceFormDetails({
  form,
  clients,
  lockedClientName,
  availableContracts,
  types,
  statuses,
  users,
  onSelectClient,
  onNumberEdited,
  onSelectIssueDate,
}: {
  form: InertiaForm<InvoiceFormData>;
  clients: Pick<Client, 'id' | 'company_name'>[];
  lockedClientName?: string;
  availableContracts: ContractOption[];
  types: Option[];
  statuses: Option[];
  users: UserOption[];
  onSelectClient: (value: string) => void;
  onNumberEdited: () => void;
  onSelectIssueDate: (value: string) => void;
}) {
  const isRecurringInvoice = form.data.type === 'recurring';
  const selectedContract = availableContracts.find(
    (contract) => String(contract.id) === form.data.contract_id,
  );

  return (
    <FormGrid className="lg:grid-cols-3">
      <Field
        label="Nomor invoice"
        htmlFor="number"
        required
        hint="Terisi otomatis mengikuti kode dan urutan klien, boleh diganti."
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
          placeholder="IM-KPN/001/08/26"
        />
      </Field>

      <Field label="Klien" required error={form.errors.client_id}>
        {lockedClientName ? (
          <Input value={lockedClientName} disabled />
        ) : (
          <Select value={form.data.client_id} onValueChange={onSelectClient}>
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

      <Field
        label="MoU terkait"
        required={isRecurringInvoice}
        hint={availableContracts.length === 0 ? 'Klien ini belum punya MoU.' : undefined}
        error={form.errors.contract_id}
      >
        <Select
          value={form.data.contract_id || 'none'}
          onValueChange={(value) => form.setData('contract_id', value === 'none' ? '' : value)}
        >
          <SelectTrigger>
            <SelectValue placeholder="Pilih MoU" />
          </SelectTrigger>
          <SelectContent>
            {!isRecurringInvoice && <SelectItem value="none">Tanpa MoU</SelectItem>}
            {availableContracts.map((contract) => (
              <SelectItem key={contract.id} value={String(contract.id)}>
                {contract.number} · {contract.title}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Field>

      {selectedContract?.is_recurring && (
        <div className="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 lg:col-span-3">
          <p className="flex items-start gap-2 text-sm">
            <TriangleAlert aria-hidden className="mt-0.5 size-4 shrink-0 text-amber-600" />
            <span>
              <span className="font-medium">{selectedContract.number}</span> adalah MoU retainer.
              Untuk tagihan periodiknya, pakai tombol{' '}
              <Link
                href={showContract(selectedContract.id)}
                className="font-medium underline underline-offset-4"
              >
                Terbitkan invoice
              </Link>{' '}
              di MoU itu. Invoice yang dibuat di sini tidak menggeser jadwal penagihannya, jadi
              periode yang sama bisa tertagih dua kali.
            </span>
          </p>
        </div>
      )}

      <Field label="Jenis" error={form.errors.type}>
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

      <Field label="Tanggal terbit" htmlFor="issue_date" required error={form.errors.issue_date}>
        <DateField
          id="issue_date"
          value={form.data.issue_date}
          required
          clearable={false}
          onChange={onSelectIssueDate}
        />
      </Field>

      <Field label="Jatuh tempo" htmlFor="due_date" required error={form.errors.due_date}>
        <DateField
          id="due_date"
          value={form.data.due_date}
          min={form.data.issue_date || undefined}
          required
          clearable={false}
          onChange={(value) => form.setData('due_date', value)}
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

      <Field
        label="Periode mulai"
        htmlFor="period_start"
        hint="Untuk tagihan retainer."
        error={form.errors.period_start}
      >
        <DateField
          id="period_start"
          value={form.data.period_start}
          onChange={(value) => form.setData('period_start', value)}
        />
      </Field>

      <Field label="Periode selesai" htmlFor="period_end" error={form.errors.period_end}>
        <DateField
          id="period_end"
          value={form.data.period_end}
          min={form.data.period_start || undefined}
          onChange={(value) => form.setData('period_end', value)}
        />
      </Field>

      <Field
        label="Catatan"
        htmlFor="notes"
        className="sm:col-span-2 lg:col-span-3"
        error={form.errors.notes}
      >
        <Textarea
          id="notes"
          rows={2}
          value={form.data.notes}
          onChange={(e) => form.setData('notes', e.target.value)}
          placeholder="Pembayaran paling lambat 14 hari sejak invoice terbit."
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
