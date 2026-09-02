import { Link, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { FormSection } from '@/components/crm/form-section';
import { InvoiceFormDetails } from '@/components/crm/invoice-form-details';
import { InvoiceFormItems } from '@/components/crm/invoice-form-items';
import type { InvoiceFormData } from '@/components/crm/invoice-form-types';
import { emptyLineItem, lineAmount } from '@/components/crm/line-items';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { nextNumber, store, update } from '@/routes/invoices';
import type {
  Client,
  CompanyProfile,
  ContractOption,
  Invoice,
  LineItem,
  Option,
  ServiceOption,
} from '@/types/crm';

type Props = {
  invoice?: Invoice;
  clientId?: number | null;
  suggestedNumber: string;
  clients: Pick<Client, 'id' | 'company_name'>[];
  contracts: ContractOption[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  cancelHref: string;
};

function today(): string {
  return new Date().toISOString().slice(0, 10);
}

const DEFAULT_DUE_DAYS = 14;

function addDays(days: number): string {
  const date = new Date();
  date.setDate(date.getDate() + days);

  return date.toISOString().slice(0, 10);
}

export function InvoiceForm({
  invoice,
  clientId,
  suggestedNumber,
  clients,
  contracts,
  services,
  company,
  types,
  statuses,
  cancelHref,
}: Props) {
  const isEdit = Boolean(invoice);
  const lockedClientName = !isEdit
    ? clients.find((client) => client.id === clientId)?.company_name
    : undefined;

  const form = useForm<InvoiceFormData>({
    number: invoice?.number ?? suggestedNumber,
    client_id: invoice ? String(invoice.client_id) : clientId ? String(clientId) : '',
    contract_id: invoice?.contract_id ? String(invoice.contract_id) : '',
    type: invoice?.type ?? 'invoice',
    issue_date: invoice?.issue_date ?? today(),
    due_date: invoice?.due_date ?? addDays(DEFAULT_DUE_DAYS),
    period_start: invoice?.period_start ?? '',
    period_end: invoice?.period_end ?? '',
    discount_amount: invoice?.discount_amount ?? '0',
    tax_percent: invoice?.tax_percent ?? '0',
    status: invoice?.status ?? 'draft',
    notes: invoice?.notes ?? company.invoice_notes ?? '',
    items: (invoice?.items?.length ? invoice.items : [{ ...emptyLineItem }]) as LineItem[],
  });

  const subtotal = form.data.items.reduce((sum, item) => sum + lineAmount(item), 0);
  const base = Math.max(subtotal - Number(form.data.discount_amount || 0), 0);
  const tax = base * (Number(form.data.tax_percent) / 100);
  const total = base + tax;

  const availableContracts = contracts.filter(
    (contract) => String(contract.client_id) === form.data.client_id,
  );

  const numberEdited = useRef(false);

  function refreshSuggestedNumber(client: string, issueDate: string) {
    if (isEdit || numberEdited.current || client === '') {
      return;
    }

    fetch(nextNumber({ query: { client, date: issueDate } }).url, {
      headers: { Accept: 'application/json' },
    })
      .then((response) => (response.ok ? response.json() : null))
      .then((payload: { number?: string } | null) => {
        if (payload?.number) {
          form.setData('number', payload.number);
        }
      })
      .catch(() => undefined);
  }

  function selectClient(value: string) {
    form.setData('client_id', value);
    form.setData('contract_id', '');

    refreshSuggestedNumber(value, form.data.issue_date);
  }

  function selectIssueDate(value: string) {
    form.setData('issue_date', value);

    refreshSuggestedNumber(form.data.client_id, value);
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && invoice ? update(invoice.id) : store(), {
          preserveScroll: true,
        });
      }}
      className="space-y-8"
    >
      <FormSection title="Data invoice">
        <InvoiceFormDetails
          form={form}
          clients={clients}
          lockedClientName={lockedClientName}
          availableContracts={availableContracts}
          types={types}
          statuses={statuses}
          onSelectClient={selectClient}
          onNumberEdited={() => {
            numberEdited.current = true;
          }}
          onSelectIssueDate={selectIssueDate}
        />
      </FormSection>

      <Separator />

      <FormSection title="Item tagihan">
        <InvoiceFormItems form={form} services={services} base={base} tax={tax} total={total} />
      </FormSection>

      <div className="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end">
        <Button type="button" variant="outline" className="w-full sm:w-auto" asChild>
          <Link href={cancelHref}>Batal</Link>
        </Button>
        <Button type="submit" className="w-full sm:w-auto" disabled={form.processing}>
          {isEdit ? 'Simpan perubahan' : 'Simpan invoice'}
        </Button>
      </div>
    </form>
  );
}
