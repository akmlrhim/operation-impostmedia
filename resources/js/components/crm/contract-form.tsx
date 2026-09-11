import { Link, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { ContractFormDetails } from '@/components/crm/contract-form-details';
import { ContractFormScope } from '@/components/crm/contract-form-scope';
import { ContractFormSignatories } from '@/components/crm/contract-form-signatories';
import type { ContractFormData } from '@/components/crm/contract-form-types';
import { FormSection } from '@/components/crm/form-section';
import { emptyLineItem, lineAmount } from '@/components/crm/line-items';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { nextNumber, store, update } from '@/routes/contracts';
import type {
  Client,
  CompanyProfile,
  Contract,
  LineItem,
  Option,
  ServiceOption,
  UserOption,
} from '@/types/crm';

type Props = {
  contract?: Contract;
  lead?: { id: number; company_name: string; estimated_value: string } | null;
  clientId?: number | null;
  suggestedNumber: string;
  clients: Pick<Client, 'id' | 'company_name'>[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
  users: UserOption[];
  aiScopePoints: boolean;
  cancelHref: string;
};

function today(): string {
  const now = new Date();

  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

export function ContractForm({
  contract,
  lead,
  clientId,
  suggestedNumber,
  clients,
  services,
  company,
  types,
  statuses,
  billingCycles,
  users,
  aiScopePoints,
  cancelHref,
}: Props) {
  const isEdit = Boolean(contract);
  const lockedClientName = !isEdit
    ? clients.find((client) => client.id === clientId)?.company_name
    : undefined;

  const form = useForm<ContractFormData>({
    number: contract?.number ?? suggestedNumber,
    client_id: contract ? String(contract.client_id) : clientId ? String(clientId) : '',
    lead_id: contract?.lead_id ? String(contract.lead_id) : lead ? String(lead.id) : '',
    type: contract?.type ?? 'mou',
    title: contract?.title ?? '',
    start_date: contract?.start_date ?? '',
    end_date: contract?.end_date ?? '',
    signing_place: contract?.signing_place ?? company.city,
    signed_date: contract?.signed_date ?? today(),
    discount_amount: contract?.discount_amount ?? '0',
    tax_percent: contract?.tax_percent ?? '0',
    billing_cycle: contract?.billing_cycle ?? 'one_time',
    next_invoice_date: contract?.next_invoice_date ?? '',
    first_party_name: contract?.first_party_name ?? '',
    first_party_position: contract?.first_party_position ?? '',
    status: contract?.status ?? 'draft',
    assigned_to_ids: contract?.assignees?.map((user) => user.id) ?? [],
    items: (contract?.items?.length ? contract.items : [{ ...emptyLineItem }]) as LineItem[],
  });

  const subtotal = form.data.items.reduce((sum, item) => sum + lineAmount(item), 0);
  const base = Math.max(subtotal - Number(form.data.discount_amount || 0), 0);
  const tax = base * (Number(form.data.tax_percent) / 100);

  const numberEdited = useRef(false);

  function selectSignedDate(value: string) {
    form.setData('signed_date', value);

    if (isEdit || numberEdited.current || value === '') {
      return;
    }

    fetch(nextNumber({ query: { date: value } }).url, {
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

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && contract ? update(contract.id) : store(), {
          preserveScroll: true,
        });
      }}
      className="space-y-8"
    >
      <FormSection title="Data kontrak">
        <ContractFormDetails
          form={form}
          isEdit={isEdit}
          clients={clients}
          lockedClientName={lockedClientName}
          types={types}
          statuses={statuses}
          billingCycles={billingCycles}
          users={users}
          onNumberEdited={() => {
            numberEdited.current = true;
          }}
          onSelectSignedDate={selectSignedDate}
        />
      </FormSection>

      <Separator />

      <FormSection title="Ruang lingkup pekerjaan">
        <ContractFormScope
          form={form}
          services={services}
          subtotal={subtotal}
          base={base}
          tax={tax}
          aiScopePoints={aiScopePoints}
        />
      </FormSection>

      <Separator />

      <FormSection title="Penanda tangan">
        <ContractFormSignatories form={form} />
      </FormSection>

      <div className="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end">
        <Button type="button" variant="outline" className="w-full sm:w-auto" asChild>
          <Link href={cancelHref}>Batal</Link>
        </Button>
        <Button type="submit" className="w-full sm:w-auto" disabled={form.processing}>
          {isEdit ? 'Simpan perubahan' : 'Simpan MoU'}
        </Button>
      </div>
    </form>
  );
}
