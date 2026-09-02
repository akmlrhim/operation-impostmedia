import { Head } from '@inertiajs/react';
import { InvoiceForm } from '@/components/crm/invoice-form';
import { PageHeader } from '@/components/crm/page-header';
import { create, index } from '@/routes/invoices';
import type { Client, CompanyProfile, ContractOption, Option, ServiceOption } from '@/types/crm';

type Props = {
  clientId: number | null;
  suggestedNumber: string;
  clients: Pick<Client, 'id' | 'company_name'>[];
  contracts: ContractOption[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
};

export default function InvoiceCreate({
  clientId,
  suggestedNumber,
  clients,
  contracts,
  services,
  company,
  types,
  statuses,
}: Props) {
  return (
    <>
      <Head title="Buat invoice" />

      <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <PageHeader title="Buat invoice" backHref={index().url} />

        <InvoiceForm
          clientId={clientId}
          suggestedNumber={suggestedNumber}
          clients={clients}
          contracts={contracts}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          cancelHref={index().url}
        />
      </div>
    </>
  );
}

InvoiceCreate.layout = {
  breadcrumbs: [
    { title: 'Invoice', href: index() },
    { title: 'Buat invoice', href: create() },
  ],
};
