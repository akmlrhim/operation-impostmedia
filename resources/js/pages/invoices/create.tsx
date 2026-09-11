import { Head } from '@inertiajs/react';
import { InvoiceForm } from '@/components/crm/invoice-form';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { create, index } from '@/routes/invoices';
import type {
  Client,
  CompanyProfile,
  ContractOption,
  Option,
  ServiceOption,
  UserOption,
} from '@/types/crm';

type Props = {
  clientId: number | null;
  suggestedNumber: string;
  clients: Pick<Client, 'id' | 'company_name'>[];
  contracts: ContractOption[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  users: UserOption[];
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
  users,
}: Props) {
  return (
    <>
      <Head title="Buat invoice" />

      <PageHeader title="Buat invoice" backHref={index().url} />

      <PageBody className="mx-auto w-full max-w-5xl">
        <InvoiceForm
          clientId={clientId}
          suggestedNumber={suggestedNumber}
          clients={clients}
          contracts={contracts}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          users={users}
          cancelHref={index().url}
        />
      </PageBody>
    </>
  );
}

InvoiceCreate.layout = {
  breadcrumbs: [
    { title: 'Invoice', href: index() },
    { title: 'Buat invoice', href: create() },
  ],
};
