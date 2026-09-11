import { Head } from '@inertiajs/react';
import { InvoiceForm } from '@/components/crm/invoice-form';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { edit, index, show } from '@/routes/invoices';
import type {
  Client,
  CompanyProfile,
  ContractOption,
  Invoice,
  Option,
  ServiceOption,
  UserOption,
} from '@/types/crm';

type Props = {
  invoice: Invoice;
  clients: Pick<Client, 'id' | 'company_name'>[];
  contracts: ContractOption[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  users: UserOption[];
};

export default function InvoiceEdit({
  invoice,
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
      <Head title={`Ubah ${invoice.number}`} />

      <PageHeader title={`Ubah ${invoice.number}`} backHref={show(invoice.id).url} />

      <PageBody className="mx-auto w-full max-w-5xl">
        <InvoiceForm
          invoice={invoice}
          suggestedNumber={invoice.number}
          clients={clients}
          contracts={contracts}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          users={users}
          cancelHref={show(invoice.id).url}
        />
      </PageBody>
    </>
  );
}

InvoiceEdit.layout = ({ invoice }: Props) => ({
  breadcrumbs: [
    { title: 'Invoice', href: index() },
    { title: invoice.number, href: show(invoice.id) },
    { title: 'Ubah', href: edit(invoice.id) },
  ],
});
